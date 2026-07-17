import 'package:dio/dio.dart';
import 'storage_service.dart';

/// Attaches the Bearer token to every outgoing request.
/// Handles 401 Unauthorized by clearing credentials so the router
/// can redirect to the login screen.
class AuthInterceptor extends Interceptor {
  /// Called by [ApiService] when a 401 is received anywhere.
  final void Function() onUnauthorized;
  bool _handlingUnauthorized = false;

  AuthInterceptor({required this.onUnauthorized});

  @override
  Future<void> onRequest(
      RequestOptions options, RequestInterceptorHandler handler) async {
    final token = await StorageService.instance.getToken();
    if (token != null) {
      options.headers['Authorization'] = 'Bearer $token';
    }
    handler.next(options);
  }

  @override
  void onError(DioException err, ErrorInterceptorHandler handler) {
    if (err.response?.statusCode == 401 && !_handlingUnauthorized) {
      _handlingUnauthorized = true;
      StorageService.instance.clear();

      // Defer navigation-related work to avoid triggering framework lifecycle
      // assertions while the current widget tree is still disposing.
      Future.microtask(() {
        try {
          onUnauthorized();
        } finally {
          _handlingUnauthorized = false;
        }
      });
    }
    handler.next(err);
  }
}

/// Converts raw [DioException] into a human-readable message string.
String extractApiError(DioException e) {
  final data = e.response?.data;
  if (data is Map) {
    // Laravel validation errors: { errors: { field: ['msg'] } }
    if (data['errors'] is Map) {
      final errors = data['errors'] as Map;
      final first = errors.values.first;
      if (first is List && first.isNotEmpty) return first.first.toString();
    }
    if (data['message'] != null) return data['message'].toString();
  }
  switch (e.type) {
    case DioExceptionType.connectionTimeout:
    case DioExceptionType.receiveTimeout:
    case DioExceptionType.sendTimeout:
      return 'Connection timed out. Check your internet.';
    case DioExceptionType.connectionError:
      return 'Cannot reach the server. Check your connection.';
    default:
      return 'Something went wrong. Please try again.';
  }
}
