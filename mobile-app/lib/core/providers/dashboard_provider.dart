import 'package:flutter/foundation.dart';
import 'package:dio/dio.dart';
import '../models/dashboard_model.dart';
import '../services/api_service.dart';

class DashboardProvider extends ChangeNotifier {
  DashboardModel? _data;
  bool _loading = false;
  String? _error;

  DashboardModel? get data => _data;
  bool get loading => _loading;
  String? get error => _error;

  Future<void> load() async {
    _loading = true;
    _error = null;
    notifyListeners();
    try {
      final raw = await ApiService.instance.getDashboard();
      _data = DashboardModel.fromJson(raw);
    } on DioException catch (e) {
      _error = e.response?.data?['message'] as String? ??
          'Failed to load dashboard.';
    }
    _loading = false;
    notifyListeners();
  }
}
