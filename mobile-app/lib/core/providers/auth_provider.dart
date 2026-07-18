import 'package:flutter/foundation.dart';
import 'package:dio/dio.dart';
import '../models/user_model.dart';
import '../services/api_service.dart';
import '../services/push_notification_service.dart';
import '../services/storage_service.dart';

enum AuthStatus { unknown, authenticated, unauthenticated }

class AuthProvider extends ChangeNotifier {
  AuthStatus _status = AuthStatus.unknown;
  UserModel? _user;
  String? _error;
  bool _loading = false;

  AuthStatus get status => _status;
  UserModel? get user => _user;
  String? get error => _error;
  bool get loading => _loading;
  bool get isAuthenticated => _status == AuthStatus.authenticated;

  Future<void> init() async {
    final token = await StorageService.instance.getToken();
    if (token == null) {
      _status = AuthStatus.unauthenticated;
      notifyListeners();
      return;
    }
    try {
      final data = await ApiService.instance.getMe();
      _user = UserModel.fromJson(data['user'] as Map<String, dynamic>);
      _status = AuthStatus.authenticated;
      await PushNotificationService.instance.syncDeviceToken();
    } catch (_) {
      await StorageService.instance.clear();
      _status = AuthStatus.unauthenticated;
    }
    notifyListeners();
  }

  Future<bool> login(String email, String password) async {
    _loading = true;
    _error = null;
    notifyListeners();
    try {
      final data = await ApiService.instance.login(email, password);
      final token = data['token'] as String;
      await StorageService.instance.saveToken(token);
      _user = UserModel.fromJson(data['user'] as Map<String, dynamic>);
      await StorageService.instance.saveUser(_user!.toJson());
      _status = AuthStatus.authenticated;
      await PushNotificationService.instance.syncDeviceToken();
      _loading = false;
      notifyListeners();
      return true;
    } on DioException catch (e) {
      _error = _extractError(e);
      _loading = false;
      notifyListeners();
      return false;
    }
  }

  Future<bool> register({
    required String name,
    required String email,
    required String password,
    String? phone,
  }) async {
    _loading = true;
    _error = null;
    notifyListeners();
    try {
      await ApiService.instance.register(
          name: name, email: email, password: password, phone: phone);
      _loading = false;
      notifyListeners();
      return true;
    } on DioException catch (e) {
      _error = _extractError(e);
      _loading = false;
      notifyListeners();
      return false;
    }
  }

  Future<void> logout() async {
    try {
      await PushNotificationService.instance.unregisterDeviceToken();
      await ApiService.instance.logout();
    } catch (_) {}
    await StorageService.instance.clear();
    _user = null;
    _status = AuthStatus.unauthenticated;
    notifyListeners();
  }

  Future<bool> updateProfile({
    required String name,
    required String email,
    String? phone,
  }) async {
    _loading = true;
    _error = null;
    notifyListeners();
    try {
      final data = await ApiService.instance.updateProfile(
        name: name,
        email: email,
        phone: phone,
      );
      final userJson = data['user'] as Map<String, dynamic>;
      _user = UserModel.fromJson(userJson);
      await StorageService.instance.saveUser(_user!.toJson());
      _loading = false;
      notifyListeners();
      return true;
    } on DioException catch (e) {
      _error = _extractError(e);
      _loading = false;
      notifyListeners();
      return false;
    }
  }

  Future<bool> uploadProfilePhoto(String filePath) async {
    _loading = true;
    _error = null;
    notifyListeners();
    try {
      final data = await ApiService.instance.uploadProfilePhoto(filePath);
      final userJson = data['user'];
      if (userJson is Map<String, dynamic>) {
        _user = UserModel.fromJson(userJson);
        await StorageService.instance.saveUser(_user!.toJson());
      } else {
        final profilePhotoUrl =
            UserModel.normalizeProfilePhotoUrl(data['profile_photo_url']);
        if (_user != null && profilePhotoUrl != null) {
          _user = _user!.copyWith(profilePhotoUrl: profilePhotoUrl);
          await StorageService.instance.saveUser(_user!.toJson());
        }
      }

      if (_user != null) {
        await StorageService.instance.saveUser(_user!.toJson());
      }
      _loading = false;
      notifyListeners();
      return true;
    } on DioException catch (e) {
      _error = _extractError(e);
      _loading = false;
      notifyListeners();
      return false;
    }
  }

  Future<bool> changePassword({
    required String currentPassword,
    required String newPassword,
  }) async {
    _loading = true;
    _error = null;
    notifyListeners();
    try {
      await ApiService.instance.changePassword(currentPassword, newPassword);
      _loading = false;
      notifyListeners();
      return true;
    } on DioException catch (e) {
      _error = _extractError(e);
      _loading = false;
      notifyListeners();
      return false;
    }
  }

  /// Handles token/session invalidation from network interceptors without
  /// making additional API requests.
  Future<void> handleUnauthorizedLocally() async {
    await StorageService.instance.clear();
    _user = null;
    _status = AuthStatus.unauthenticated;
    notifyListeners();
  }

  void clearError() {
    _error = null;
    notifyListeners();
  }

  String _extractError(DioException e) {
    final data = e.response?.data;
    if (data is Map && data['message'] != null) {
      return data['message'] as String;
    }
    if (e.type == DioExceptionType.connectionTimeout ||
        e.type == DioExceptionType.receiveTimeout) {
      return 'Connection timed out. Check your internet.';
    }
    if (e.type == DioExceptionType.connectionError) {
      return 'Unable to reach server. Please check your internet and try again.';
    }
    return 'Something went wrong. Please try again.';
  }
}
