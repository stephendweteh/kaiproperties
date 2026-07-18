import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import '../constants/app_constants.dart';
import 'auth_interceptor.dart';

class ApiService {
  ApiService._();
  static final ApiService instance = ApiService._();

  void Function()? _onUnauthorized;
  late final Dio _dio;

  /// Call once at app startup (in main.dart) before any requests.
  void init({required void Function() onUnauthorized}) {
    _onUnauthorized = onUnauthorized;
    _dio = Dio(
      BaseOptions(
        baseUrl: kBaseUrl,
        connectTimeout: const Duration(seconds: 30),
        receiveTimeout: const Duration(seconds: 30),
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      ),
    );
    _dio.interceptors.add(AuthInterceptor(onUnauthorized: onUnauthorized));
    if (kDebugMode) {
      _dio.interceptors.add(
        LogInterceptor(
          request: true,
          requestHeader: false,
          requestBody: true,
          responseHeader: false,
          responseBody: true,
          error: true,
          logPrint: (obj) => debugPrint(obj.toString()),
        ),
      );
    }
  }

  Dio get dio {
    assert(
      _onUnauthorized != null,
      'ApiService.init() must be called before making requests.',
    );
    return _dio;
  }

  Future<Response<dynamic>> _getWithApiV1Fallback(String path) async {
    try {
      return await dio.get(path);
    } on DioException catch (e) {
      final status = e.response?.statusCode;
      if (status != 404 && status != 405 && status != 500) {
        rethrow;
      }

      final baseUri = Uri.parse(kBaseUrl);
      final fallbackUri = Uri(
        scheme: baseUri.scheme,
        host: baseUri.host,
        port: baseUri.hasPort ? baseUri.port : null,
        path: '/api/v1$path',
      ).toString();

      debugPrint(
        'Primary mobile endpoint failed ($status) for $path, trying fallback at $fallbackUri',
      );

      return dio.getUri(Uri.parse(fallbackUri));
    }
  }

  // ─── Auth ──────────────────────────────────────────────────────────────────

  Future<Map<String, dynamic>> login(String email, String password) async {
    final res = await dio.post(
      '/auth/login',
      data: {'email': email, 'password': password},
    );
    return res.data as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> register({
    required String name,
    required String email,
    required String password,
    String? phone,
  }) async {
    final res = await dio.post(
      '/auth/register',
      data: {
        'name': name,
        'email': email,
        'password': password,
        'password_confirmation': password,
        if (phone != null && phone.isNotEmpty) 'phone': phone,
      },
    );
    return res.data as Map<String, dynamic>;
  }

  Future<void> logout() async => dio.post('/auth/logout');

  Future<Map<String, dynamic>> getMe() async {
    final res = await dio.get('/auth/me');
    return res.data as Map<String, dynamic>;
  }

  Future<void> changePassword(String current, String next) async {
    await dio.post(
      '/auth/change-password',
      data: {
        'current_password': current,
        'password': next,
        'password_confirmation': next,
      },
    );
  }

  // ─── Profile ───────────────────────────────────────────────────────────────

  Future<Map<String, dynamic>> updateProfile({
    String? name,
    String? phone,
    String? email,
  }) async {
    final res = await dio.patch(
      '/profile',
      data: {
        if (name != null && name.isNotEmpty) 'name': name,
        if (phone != null && phone.isNotEmpty) 'phone': phone,
        if (email != null && email.isNotEmpty) 'email': email,
      },
    );
    return res.data as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> uploadProfilePhoto(String filePath) async {
    final formData = FormData.fromMap({
      'photo': await MultipartFile.fromFile(filePath, filename: 'photo.jpg'),
    });
    final res = await dio.post(
      '/profile/photo',
      data: formData,
      options: Options(contentType: 'multipart/form-data'),
    );
    return res.data as Map<String, dynamic>;
  }

  // ─── Dashboard ─────────────────────────────────────────────────────────────

  Future<Map<String, dynamic>> getDashboard() async {
    final res = await dio.get('/dashboard');
    return res.data as Map<String, dynamic>;
  }

  // ─── References ────────────────────────────────────────────────────────────

  Future<List<dynamic>> getProperties() async {
    final res = await _getWithApiV1Fallback('/references/properties');
    return (res.data as Map<String, dynamic>)['data'] as List<dynamic>;
  }

  Future<List<dynamic>> getCategories() async {
    final res = await _getWithApiV1Fallback('/references/categories');
    return (res.data as Map<String, dynamic>)['data'] as List<dynamic>;
  }

  Future<List<dynamic>> getTechnicians() async {
    final res = await _getWithApiV1Fallback('/references/technicians');
    return (res.data as Map<String, dynamic>)['data'] as List<dynamic>;
  }

  Future<List<dynamic>> getReporters() async {
    final res = await _getWithApiV1Fallback('/references/reporters');
    return (res.data as Map<String, dynamic>)['data'] as List<dynamic>;
  }

  // ─── Tickets ───────────────────────────────────────────────────────────────

  Future<Map<String, dynamic>> getTickets({
    String? status,
    String? priority,
    String? search,
    int page = 1,
    int perPage = 15,
  }) async {
    final res = await dio.get(
      '/tickets',
      queryParameters: {
        if (status != null && status.isNotEmpty) 'status': status,
        if (priority != null && priority.isNotEmpty) 'priority': priority,
        if (search != null && search.isNotEmpty) 'search': search,
        'page': page,
        'per_page': perPage,
      },
    );
    return res.data as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> getTicket(int id) async {
    final res = await dio.get('/tickets/$id');
    return res.data as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> createTicket(Map<String, dynamic> data) async {
    final res = await dio.post('/tickets', data: data);
    return res.data as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> updateTicket(
    int id,
    Map<String, dynamic> data,
  ) async {
    final res = await dio.patch('/tickets/$id', data: data);
    return res.data as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> changeTicketStatus(int id, String status) async {
    final res = await dio.patch(
      '/tickets/$id/status',
      data: {'status': status},
    );
    return res.data as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> assignTicket(int id, int assignedTo) async {
    final res = await dio.patch(
      '/tickets/$id/assign',
      data: {'assigned_to': assignedTo},
    );
    return res.data as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> getTicketPhases(int ticketId) async {
    final res = await dio.get('/tickets/$ticketId/phases');
    return res.data as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> addTicketPhase(
    int ticketId,
    Map<String, dynamic> data,
  ) async {
    final res = await dio.post('/tickets/$ticketId/phases', data: data);
    return res.data as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> updateTicketPhase(
    int ticketId,
    int phaseId,
    Map<String, dynamic> data,
  ) async {
    final res = await dio.patch('/tickets/$ticketId/phases/$phaseId', data: data);
    return res.data as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> completeTicketPhase(
    int ticketId,
    int phaseId,
  ) async {
    final res = await dio.patch('/tickets/$ticketId/phases/$phaseId/complete');
    return res.data as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> uploadPhaseAttachment({
    required int ticketId,
    required int phaseId,
    required String filePath,
    String? attachmentType,
  }) async {
    final fileName = filePath.split(RegExp(r'[\\/]')).last;
    final formData = FormData.fromMap({
      'file': await MultipartFile.fromFile(filePath, filename: fileName),
      if (attachmentType != null && attachmentType.isNotEmpty)
        'attachment_type': attachmentType,
    });

    final res = await dio.post(
      '/tickets/$ticketId/phases/$phaseId/attachments',
      data: formData,
      options: Options(contentType: 'multipart/form-data'),
    );

    return res.data as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> submitCostRequest(
    int ticketId,
    double amount,
    String reason,
  ) async {
    final res = await dio.post(
      '/tickets/$ticketId/cost-requests',
      data: {'amount': amount, 'reason': reason},
    );
    return res.data as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> uploadTicketAttachment({
    required int ticketId,
    required String filePath,
    String? attachmentType,
  }) async {
    final fileName = filePath.split(RegExp(r'[\\/]')).last;
    final formData = FormData.fromMap({
      'file': await MultipartFile.fromFile(filePath, filename: fileName),
      if (attachmentType != null && attachmentType.isNotEmpty)
        'attachment_type': attachmentType,
    });

    final res = await dio.post(
      '/tickets/$ticketId/attachments',
      data: formData,
      options: Options(contentType: 'multipart/form-data'),
    );

    return res.data as Map<String, dynamic>;
  }

  // ─── Device tokens ─────────────────────────────────────────────────────────

  Future<void> registerDeviceToken(
    String token,
    String platform,
    String? deviceName,
  ) async {
    await dio.post(
      '/device-tokens',
      data: {
        'token': token,
        'platform': platform,
        if (deviceName != null && deviceName.isNotEmpty)
          'device_name': deviceName,
      },
    );
  }
}
