package app.nido;

import android.provider.Settings;
import android.content.Intent;

import com.getcapacitor.JSObject;
import com.getcapacitor.Plugin;
import com.getcapacitor.PluginCall;
import com.getcapacitor.annotation.CapacitorPlugin;

@CapacitorPlugin(name = "DeviceIdentity")
public class DeviceIdentityPlugin extends Plugin {
    @com.getcapacitor.PluginMethod
    public void getAndroidId(PluginCall call) {
        String androidId = Settings.Secure.getString(
            getContext().getContentResolver(),
            Settings.Secure.ANDROID_ID
        );
        if (androidId == null || androidId.isBlank()) {
            call.reject("android_id_unavailable");
            return;
        }
        JSObject result = new JSObject();
        result.put("deviceId", androidId);
        call.resolve(result);
    }

    @com.getcapacitor.PluginMethod
    public void returnToAndroidDesktop(PluginCall call) {
        Intent intent = new Intent(Intent.ACTION_MAIN);
        intent.addCategory(Intent.CATEGORY_HOME);
        intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK);
        getContext().startActivity(intent);
        call.resolve();
    }
}
