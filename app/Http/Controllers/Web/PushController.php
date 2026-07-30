<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\Request;

class PushController extends Controller
{
    public function storeDeviceToken(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:500'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        DeviceToken::updateOrCreate(
            ['token' => $validated['token']],
            [
                'user_id' => $request->user()->id,
                'platform' => 'web',
                'device_name' => $validated['device_name'] ?? null,
            ]
        );

        return response()->json(['message' => 'Web device token registered.'], 201);
    }

    public function destroyDeviceToken(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:500'],
        ]);

        DeviceToken::query()
            ->where('user_id', $request->user()->id)
            ->where('token', $validated['token'])
            ->delete();

        return response()->json(['message' => 'Web device token removed.']);
    }

    public function firebaseMessagingServiceWorker()
    {
        $config = [
            'apiKey' => (string) config('services.firebase.web.api_key'),
            'authDomain' => (string) config('services.firebase.web.auth_domain'),
            'projectId' => (string) config('services.firebase.web.project_id'),
            'storageBucket' => (string) config('services.firebase.web.storage_bucket'),
            'messagingSenderId' => (string) config('services.firebase.web.messaging_sender_id'),
            'appId' => (string) config('services.firebase.web.app_id'),
        ];

        $configJson = json_encode($config, JSON_UNESCAPED_SLASHES);

        $script = <<<JS
importScripts('https://www.gstatic.com/firebasejs/10.12.5/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.12.5/firebase-messaging-compat.js');

const firebaseConfig = {$configJson};

if (firebaseConfig.apiKey && firebaseConfig.projectId && firebaseConfig.messagingSenderId && firebaseConfig.appId) {
  firebase.initializeApp(firebaseConfig);
  const messaging = firebase.messaging();

  messaging.onBackgroundMessage((payload) => {
    const notification = payload.notification || {};
    const title = notification.title || 'KAI Properties';
    const body = notification.body || 'You have a new update.';
    const link = payload?.fcmOptions?.link || payload?.data?.link || '/';

    self.registration.showNotification(title, {
      body,
      data: { link },
      icon: '/kaipwa.png',
      badge: '/kaipwa.png',
    });
  });
}

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const link = event.notification?.data?.link || '/';

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
      for (const client of clientList) {
        if ('focus' in client) {
          client.focus();
          if ('navigate' in client) {
            client.navigate(link);
          }
          return;
        }
      }

      if (clients.openWindow) {
        return clients.openWindow(link);
      }
    })
  );
});
JS;

        return response($script, 200, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
        ]);
    }
}
