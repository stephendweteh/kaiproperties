/// Live API base URL (production).
/// The emulator cannot reliably resolve the production hostname, so we use the
/// resolved IP by default and send the original Host header to the server.
const String kLiveApiBaseUrl = 'https://portal.kaipropertiesgh.com/api/mobile/v1';
const String kFallbackApiBaseUrl = 'https://82.29.189.76/api/mobile/v1';
const String kApiHostHeader = 'portal.kaipropertiesgh.com';
const String kBaseUrl = String.fromEnvironment(
  'API_BASE_URL',
  defaultValue: kLiveApiBaseUrl,
);

const String kAppName = 'KAI Properties';
const String kTokenKey = 'auth_token';
const String kUserKey = 'auth_user';
