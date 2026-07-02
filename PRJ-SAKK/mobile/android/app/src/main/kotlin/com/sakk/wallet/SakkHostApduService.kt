package com.sakk.wallet

import android.nfc.cardemulation.HostApduService
import android.os.Bundle
import java.nio.charset.StandardCharsets

/**
 * Host Card Emulation service for SAKK tap-to-pay.
 *
 * When another SAKK phone taps this device, Android routes the SELECT AID
 * command (F0 SAKK 01) to this service. It responds with the stored payment
 * payload (a URI or account number) that the reader decodes.
 *
 * The payload is set by NfcHcePlugin.startEmulation() / startPaymentBroadcast()
 * via the Flutter method channel.
 */
class SakkHostApduService : HostApduService() {

    companion object {
        private var payload: String? = null

        /** Set the payment data that will be returned on the next NFC tap. */
        @JvmStatic fun setPayload(data: String?) { payload = data }

        /** Get the current payment data. */
        @JvmStatic fun getPayload(): String? = payload

        /** Clear the payment data (stop broadcasting). */
        @JvmStatic fun clearPayload() { payload = null }
    }

    /** Our registered AID bytes: F0 SAKK 01 */
    private val sakkAid = byteArrayOf(
        0xF0.toByte(), 0x53, 0x41, 0x4B, 0x4B, 0x01
    )

    /** Status word: success */
    private val swSuccess = byteArrayOf(0x90.toByte(), 0x00)

    /** Status word: not found / AID not recognised */
    private val swNotFound = byteArrayOf(0x6A.toByte(), 0x82.toByte())

    override fun onCreate() {
        super.onCreate()
    }

    override fun onStartCommand(intent: android.content.Intent?, flags: Int, startId: Int): Int {
        return START_STICKY
    }

    /**
     * Called by Android when the reader sends an APDU command.
     *
     * We only handle SELECT by AID (F0 SAKK 01). Everything else gets
     * "not found" so the OS can try other services.
     */
    override fun processCommandApdu(
        commandApdu: ByteArray,
        extras: Bundle?
    ): ByteArray {
        // --- SELECT AID -------------------------------------------------
        if (isSelectAidCommand(commandApdu, sakkAid)) {
            val currentPayload = getPayload()
            if (currentPayload != null) {
                val payloadBytes = currentPayload.toByteArray(StandardCharsets.UTF_8)
                val response = ByteArray(payloadBytes.size + 2)
                System.arraycopy(payloadBytes, 0, response, 0, payloadBytes.size)
                response[payloadBytes.size] = swSuccess[0]
                response[payloadBytes.size + 1] = swSuccess[1]
                return response
            }
            // Payload not set — still acknowledge the AID so the reader
            // gets a clean failure instead of "service not found".
            return swSuccess
        }

        // --- Unknown command --------------------------------------------
        // Return "not found" so Android falls through to next service.
        return swNotFound
    }

    /**
     * Called when the reader stops communicating (taps away / times out).
     */
    override fun onDeactivated(reason: Int) {
        // No cleanup needed — payload persists across taps until
        // the user explicitly stops the broadcast.
    }

    // -----------------------------------------------------------------
    // APDU helpers
    // -----------------------------------------------------------------

    /**
     * Check whether [commandApdu] is a SELECT (by name) for [expectedAid].
     *
     * Format: CLA INS P1 P2 Lc <AID-bytes> [Le]
     *   CLA  = 00 (or 0x80-0x8F for interindustry)
     *   INS  = A4 (SELECT)
     *   P1   = 04 (select by DF name)
     *   P2   = 00 (no options)
     *   Lc   = length of AID
     *   Le   = 00 (optional, max expected response)
     */
    private fun isSelectAidCommand(
        commandApdu: ByteArray,
        expectedAid: ByteArray
    ): Boolean {
        if (commandApdu.size < 6 + expectedAid.size) return false

        // Accept CLA=00 or CLA=80-8F (interindustry class)
        val cla = commandApdu[0].toInt() and 0xFF
        if (cla != 0x00 && (cla and 0xF0) != 0x80) return false

        // INS must be SELECT (A4)
        if (commandApdu[1].toInt() != 0xA4) return false
        // P1 must be "select by DF name" (04)
        if (commandApdu[2].toInt() != 0x04) return false
        // P2 must be 00
        if (commandApdu[3].toInt() != 0x00) return false

        val lc = commandApdu[4].toInt() and 0xFF
        if (lc != expectedAid.size) return false

        // Compare AID bytes
        for (i in expectedAid.indices) {
            if (commandApdu[5 + i] != expectedAid[i]) return false
        }

        return true
    }
}
