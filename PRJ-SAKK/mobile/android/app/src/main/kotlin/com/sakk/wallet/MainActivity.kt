package com.sakk.wallet

import android.content.Intent
import android.nfc.NfcAdapter
import io.flutter.embedding.android.FlutterFragmentActivity
import io.flutter.embedding.engine.FlutterEngine

class MainActivity : FlutterFragmentActivity() {
    override fun configureFlutterEngine(flutterEngine: FlutterEngine) {
        super.configureFlutterEngine(flutterEngine)
        val plugin = NfcHcePlugin()
        flutterEngine.plugins.add(plugin)

        // Cold-start: check if launched via NFC tap
        val nfcData = readNfcTagFromIntent(intent)
        if (nfcData != null) {
            plugin.setColdNfcData(nfcData)
        }
    }

    /** Handle NFC taps that arrive while the activity is already running. */
    override fun onNewIntent(intent: Intent) {
        super.onNewIntent(intent)
        val nfcData = readNfcTagFromIntent(intent)
        if (nfcData != null) {
            // Forward to Flutter via the HCE plugin's warm-tap mechanism
            setIntent(intent)
            // The plugin's method channel will pick this up when
            // Flutter calls getInitialNfcUri.
            NfcHcePlugin.coldNfcData = nfcData
        }
    }

    /**
     * If [intent] was triggered by an NFC tag discovery (TECH_DISCOVERED or
     * NDEF_DISCOVERED), read the SAKK payment payload via IsoDep SELECT AID
     * and return it. Returns null for non-NFC intents.
     */
    private fun readNfcTagFromIntent(intent: Intent): String? {
        if (intent.action != NfcAdapter.ACTION_TECH_DISCOVERED &&
            intent.action != NfcAdapter.ACTION_NDEF_DISCOVERED &&
            intent.action != NfcAdapter.ACTION_TAG_DISCOVERED) {
            return null
        }
        val tag = intent.getParcelableExtra<android.nfc.Tag>(NfcAdapter.EXTRA_TAG) ?: return null
        return SakkTagReader.readTag(tag)
    }
}
