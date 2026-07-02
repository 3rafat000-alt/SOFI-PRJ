package com.sakk.wallet

import android.nfc.Tag
import android.nfc.tech.IsoDep
import java.nio.charset.StandardCharsets

/**
 * Reads a SAKK payment payload from an NFC tag (or HCE emulation on another
 * phone) by sending a SELECT AID command over IsoDep.
 *
 * This is used for cold-start NFC launches: when the app is not running and
 * the user taps another SAKK phone, Android launches our Activity with the
 * tag handle. We read the tag here (in native code) because the Flutter
 * engine hasn't started yet.
 */
object SakkTagReader {

    /** SELECT AID APDU for F0 SAKK 01: 00 A4 04 00 06 F0 53 41 4B 4B 01 00 */
    private val selectSakkAid = byteArrayOf(
        0x00, 0xA4.toByte(), 0x04, 0x00, 0x06,
        0xF0.toByte(), 0x53, 0x41, 0x4B, 0x4B, 0x01, 0x00
    )

    /**
     * Read the SAKK payment payload from [tag].
     * Returns the payload string (e.g. "SAKKPAY|SK0001|500|SYP|John"), or
     * null if the tag doesn't respond to our AID or doesn't support IsoDep.
     */
    fun readTag(tag: Tag): String? {
        return try {
            val isoDep = IsoDep.get(tag) ?: return null
            isoDep.connect()
            val response = isoDep.transceive(selectSakkAid)
            isoDep.close()
            parseResponse(response)
        } catch (e: Exception) {
            null
        }
    }

    /**
     * Strip trailing status word (90 00) and decode as UTF-8.
     */
    private fun parseResponse(response: ByteArray): String? {
        if (response.size < 2) return null
        val sw1 = response[response.size - 2].toInt() and 0xFF
        val sw2 = response[response.size - 1].toInt() and 0xFF
        if (sw1 != 0x90 || sw2 != 0x00) return null
        val payload = response.copyOfRange(0, response.size - 2)
        if (payload.isEmpty()) return null
        return String(payload, StandardCharsets.UTF_8).trim()
    }
}
