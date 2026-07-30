import 'package:flutter/foundation.dart';
import 'package:dio/dio.dart';
import '../models/ticket_model.dart';
import '../services/api_service.dart';

class TicketProvider extends ChangeNotifier {
  List<TicketModel> _tickets = [];
  TicketModel? _selected;
  bool _loading = false;
  bool _loadingMore = false;
  String? _error;
  String? _lastCreateMessage;
  String? _lastCreatedTicketNo;
  int? _lastCreatedTicketId;
  int _currentPage = 1;
  bool _hasMore = true;
  String? _activeStatus;
  String? _search;

  List<TicketModel> get tickets => _tickets;
  TicketModel? get selected => _selected;
  bool get loading => _loading;
  bool get loadingMore => _loadingMore;
  String? get error => _error;
  String? get lastCreateMessage => _lastCreateMessage;
  String? get lastCreatedTicketNo => _lastCreatedTicketNo;
  int? get lastCreatedTicketId => _lastCreatedTicketId;
  bool get hasMore => _hasMore;

  Future<void> loadTickets({
    String? status,
    String? search,
    bool reset = true,
  }) async {
    if (reset) {
      _currentPage = 1;
      _hasMore = true;
      _tickets = [];
      _activeStatus = status;
      _search = search;
    }
    if (_loading || (!_hasMore && !reset)) return;
    _loading = reset;
    _loadingMore = !reset;
    _error = null;
    notifyListeners();

    try {
      final data = await ApiService.instance.getTickets(
        status: _activeStatus,
        search: _search,
        page: _currentPage,
      );
      final items = (data['data'] as List<dynamic>)
          .map((e) => TicketModel.fromJson(e as Map<String, dynamic>))
          .toList();
      final meta = data['meta'] as Map<String, dynamic>?;
      _hasMore = meta != null
          ? _currentPage < (meta['last_page'] as int)
          : items.length >= 15;
      if (reset) {
        _tickets = items;
      } else {
        _tickets.addAll(items);
      }
      _currentPage++;
    } on DioException catch (e) {
      _error =
          e.response?.data?['message'] as String? ?? 'Failed to load tasks.';
    }
    _loading = false;
    _loadingMore = false;
    notifyListeners();
  }

  Future<void> loadMore() =>
      loadTickets(status: _activeStatus, search: _search, reset: false);

  Future<bool> loadTicket(int id) async {
    _loading = true;
    _error = null;
    notifyListeners();
    try {
      final data = await ApiService.instance.getTicket(id);
      _selected = TicketModel.fromJson(data['data'] as Map<String, dynamic>);
      _loading = false;
      notifyListeners();
      return true;
    } on DioException catch (e) {
      _error =
          e.response?.data?['message'] as String? ?? 'Failed to load task.';
      _loading = false;
      notifyListeners();
      return false;
    }
  }

  Future<bool> changeStatus(int id, String status) async {
    try {
      final data = await ApiService.instance.changeTicketStatus(id, status);
      final updated = TicketModel.fromJson(
        data['data'] as Map<String, dynamic>,
      );
      final idx = _tickets.indexWhere((t) => t.id == id);
      if (idx != -1) _tickets[idx] = updated;
      if (_selected?.id == id) _selected = updated;
      notifyListeners();
      return true;
    } on DioException catch (e) {
      _error =
          e.response?.data?['message'] as String? ?? 'Failed to update status.';
      notifyListeners();
      return false;
    }
  }

  Future<bool> assignTicket(int id, int assignedTo) async {
    try {
      final data = await ApiService.instance.assignTicket(id, assignedTo);
      final updated = TicketModel.fromJson(
        data['data'] as Map<String, dynamic>,
      );
      final idx = _tickets.indexWhere((t) => t.id == id);
      if (idx != -1) _tickets[idx] = updated;
      if (_selected?.id == id) _selected = updated;
      notifyListeners();
      return true;
    } on DioException catch (e) {
      _error =
          e.response?.data?['message'] as String? ?? 'Failed to assign task.';
      notifyListeners();
      return false;
    }
  }

  Future<bool> createTicket(Map<String, dynamic> data) async {
    try {
      _lastCreateMessage = null;
      _lastCreatedTicketNo = null;
      _lastCreatedTicketId = null;
      final res = await ApiService.instance.createTicket(data);
      final payload = res['data'] as Map<String, dynamic>?;
      _lastCreateMessage = res['message'] as String?;
      _lastCreatedTicketNo = payload?['ticket_no'] as String?;
      _lastCreatedTicketId = payload?['id'] as int?;
      await loadTickets(reset: true);
      return true;
    } on DioException catch (e) {
      _error =
          e.response?.data?['message'] as String? ?? 'Failed to create task.';
      notifyListeners();
      return false;
    }
  }

  Future<bool> updateTicket(int id, Map<String, dynamic> data) async {
    try {
      final res = await ApiService.instance.updateTicket(id, data);
      final updated = TicketModel.fromJson(res['data'] as Map<String, dynamic>);
      final idx = _tickets.indexWhere((t) => t.id == id);
      if (idx != -1) _tickets[idx] = updated;
      if (_selected?.id == id) _selected = updated;
      notifyListeners();
      return true;
    } on DioException catch (e) {
      _error =
          e.response?.data?['message'] as String? ?? 'Failed to update task.';
      notifyListeners();
      return false;
    }
  }

  void clearError() {
    _error = null;
    notifyListeners();
  }
}
