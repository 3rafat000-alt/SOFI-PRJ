package com.sakk.wallet

import android.app.Activity
import android.content.Context
import android.nfc.NfcManager
import io.flutter.embedding.engine.plugins.FlutterPlugin
import io.flutter.embedding.engine.plugins.activity.ActivityAware
import io.flutter.embedding.engine.plugins.activity.ActivityPluginBinding
import io.flutter.plugin.common.MethodCall
import io.flutter.plugin.common.MethodChannel
import io.flutter.plugin.common.MethodChannel.MethodCallHandler

class NfcHcePlugin : FlutterPlugin, MethodCallHandler, ActivityAware {
    companion object {
        /** NFC tag data read from a cold-start NFC tap (set by MainActivity). */
        var coldNfcData: String? = null
    }

    private lateinit var channel: MethodChannel
    private var context: Context? = null
    private var activity: Activity? = null

    /** Called by MainActivity to set cold-start NFC data after construction. */
    fun setColdNfcData(data: String) { coldNfcData = data }

    override fun onAttachedToEngine(binding: FlutterPlugin.FlutterPluginBinding) {
        context = binding.applicationContext
        channel = MethodChannel(binding.binaryMessenger, "sakk/nfc_hce")
        channel.setMethodCallHandler(this)
    }

    override fun onDetachedFromEngine(binding: FlutterPlugin.FlutterPluginBinding) {
        channel.setMethodCallHandler(null)
    }

    override fun onAttachedToActivity(binding: ActivityPluginBinding) {
        activity = binding.activity
    }

    override fun onDetachedFromActivity() {
        activity = null
    }

    override fun onDetachedFromActivityForConfigChanges() {
        activity = null
    }

    override fun onReattachedToActivityForConfigChanges(binding: ActivityPluginBinding) {
        activity = binding.activity
    }

    override fun onMethodCall(call: MethodCall, result: MethodChannel.Result) {
        when (call.method) {
            "isSupported" -> {
                val nfcManager = context?.getSystemService(Context.NFC_SERVICE) as? NfcManager
                val adapter = nfcManager?.defaultAdapter
                result.success(adapter != null && adapter.isEnabled)
            }
            "startEmulation" -> {
                val payload = call.argument<String>("account")
                SakkHostApduService.setPayload(payload)
                result.success(true)
            }
            "stopEmulation" -> {
                SakkHostApduService.clearPayload()
                result.success(true)
            }
            "setPreferred" -> {
                // Foreground dispatch would go here, but for the MVP the
                // AID routing via manifest is sufficient.
                result.success(true)
            }
            "startPaymentBroadcast" -> {
                val account = call.argument<String>("account")
                SakkHostApduService.setPayload(account)
                result.success(true)
            }
            "getInitialNfcUri" -> {
                // Priority: 1) NFC tag data from cold-start tap, 2) intent URI
                val nfcData = coldNfcData?.also { coldNfcData = null }
                if (nfcData != null) {
                    result.success(nfcData)
                } else {
                    val uri = activity?.intent?.data?.toString()
                    result.success(uri)
                }
            }
            "setHandler" -> {
                result.success(true)
            }
            else -> result.notImplemented()
        }
    }
}
