import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';

import '../../core/constants/app_colors.dart';
import '../../core/providers/auth_provider.dart';
import '../../core/providers/ticket_provider.dart';
import '../../core/services/api_service.dart';

class TaskCreateScreen extends StatefulWidget {
  final int? ticketId;
  const TaskCreateScreen({super.key, this.ticketId});

  @override
  State<TaskCreateScreen> createState() => _TaskCreateScreenState();
}

class _TaskCreateScreenState extends State<TaskCreateScreen> {
  static const _currencies = ['GBP', 'USD', 'EUR', 'GHS', 'CNY'];

  final _formKey = GlobalKey<FormState>();
  final _titleCtrl = TextEditingController();
  final _descCtrl = TextEditingController();
  final _unitCtrl = TextEditingController();
  final _estimatedCostCtrl = TextEditingController();

  final _imagePicker = ImagePicker();

  List<Map<String, dynamic>> _properties = [];
  List<Map<String, dynamic>> _categories = [];
  List<Map<String, dynamic>> _technicians = [];
  List<Map<String, dynamic>> _reporters = [];

  int? _propertyId;
  int? _categoryId;
  int? _assignedTo;
  int? _reportedBy;

  String _priority = 'medium';
  String _estimatedCostCurrency = 'GHS';
  DateTime? _etd;

  final List<String> _imageAttachmentPaths = [];
  String? _cameraAttachmentPath;
  final List<String> _documentAttachmentPaths = [];

  bool _loading = false;
  bool _submitting = false;
  String? _error;

  bool get _isEditMode => widget.ticketId != null;

  bool get _isReporterScopedRole {
    final user = context.read<AuthProvider>().user;
    if (user == null) return true;
    return user.isTenant || user.isManagingDirector || user.isGeneralManager;
  }

  @override
  void initState() {
    super.initState();
    _loadRefs();
  }

  int? _asInt(dynamic value) {
    if (value is int) return value;
    if (value is String) return int.tryParse(value);
    return null;
  }

  Future<void> _loadRefs() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    var currentUser = context.read<AuthProvider>().user;
    final failedRefs = <String>[];

    List<dynamic> props = const [];
    List<dynamic> cats = const [];
    List<dynamic> technicians = const [];
    List<dynamic> reporters = const [];
    Map<String, dynamic>? ticketData;

    if (!_isReporterScopedRole && currentUser != null) {
      reporters = [
        {
          'id': currentUser.id,
          'name': currentUser.name,
        }
      ];
    }

    try {
      props = await ApiService.instance.getProperties();
    } catch (_) {
      failedRefs.add('properties');
    }

    try {
      cats = await ApiService.instance.getCategories();
    } catch (_) {
      failedRefs.add('categories');
    }

    if (!_isReporterScopedRole) {
      try {
        technicians = await ApiService.instance.getTechnicians();
      } catch (_) {
        failedRefs.add('technicians');
      }

      try {
        final remoteReporters = await ApiService.instance.getReporters();
        reporters = [...reporters, ...remoteReporters];
      } catch (_) {
        failedRefs.add('reporters');
        if (currentUser != null) {
          reporters = [
            {
              'id': currentUser.id,
              'name': currentUser.name,
            }
          ];
        }
      }
    }

    if (_isEditMode) {
      try {
        final ticketRes = await ApiService.instance.getTicket(widget.ticketId!);
        ticketData = ticketRes['data'] as Map<String, dynamic>;
      } catch (_) {
        failedRefs.add('ticket');
      }
    }

    if (!_isReporterScopedRole && currentUser == null) {
      try {
        final meRes = await ApiService.instance.getMe();
        final me = meRes['user'];
        if (me is Map<String, dynamic>) {
          final meId = _asInt(me['id']);
          final meName = me['name'] as String?;
          if (meId != null && meName != null) {
            reporters = [
              {'id': meId, 'name': meName}
            ];
          }
        }
      } catch (_) {
        // Ignore; other fallbacks below will still run.
      }
    }

    final needTicketFallback =
        props.isEmpty ||
        cats.isEmpty ||
        (!_isReporterScopedRole && (technicians.isEmpty || reporters.isEmpty));

    if (needTicketFallback) {
      try {
        final ticketsRes = await ApiService.instance.getTickets(
          page: 1,
          perPage: 100,
        );
        final ticketItems = (ticketsRes['data'] as List<dynamic>)
            .cast<Map<String, dynamic>>();

        if (props.isEmpty) {
          final propertyMap = <int, Map<String, dynamic>>{};
          for (final item in ticketItems) {
            final property = item['property'];
            if (property is Map<String, dynamic>) {
              final id = _asInt(property['id']);
              final name = property['name'] as String?;
              if (id != null && name != null) {
                propertyMap[id] = {'id': id, 'name': name};
              }
            }
          }
          if (propertyMap.isNotEmpty) {
            props = propertyMap.values.toList();
            failedRefs.remove('properties');
          }
        }

        if (cats.isEmpty) {
          final categoryMap = <int, Map<String, dynamic>>{};
          for (final item in ticketItems) {
            final category = item['category'];
            if (category is Map<String, dynamic>) {
              final id = _asInt(category['id']);
              final name = category['name'] as String?;
              if (id != null && name != null) {
                categoryMap[id] = {'id': id, 'name': name};
              }
            }
          }
          if (categoryMap.isNotEmpty) {
            cats = categoryMap.values.toList();
            failedRefs.remove('categories');
          }
        }

        if (!_isReporterScopedRole && technicians.isEmpty) {
          final technicianMap = <int, Map<String, dynamic>>{};
          for (final item in ticketItems) {
            final technician = item['technician'];
            if (technician is Map<String, dynamic>) {
              final id = _asInt(technician['id']);
              final name = technician['name'] as String?;
              if (id != null && name != null) {
                technicianMap[id] = {'id': id, 'name': name};
              }
            }
          }
          if (technicianMap.isNotEmpty) {
            technicians = technicianMap.values.toList();
            failedRefs.remove('technicians');
          }
        }

        if (!_isReporterScopedRole && reporters.isEmpty) {
          final reporterMap = <int, Map<String, dynamic>>{};
          for (final item in ticketItems) {
            final reporter = item['reporter'];
            if (reporter is Map<String, dynamic>) {
              final id = _asInt(reporter['id']);
              final name = reporter['name'] as String?;
              if (id != null && name != null) {
                reporterMap[id] = {'id': id, 'name': name};
              }
            }
          }

          if (currentUser != null) {
            reporterMap[currentUser.id] = {
              'id': currentUser.id,
              'name': currentUser.name,
            };
          }

          if (reporterMap.isNotEmpty) {
            reporters = reporterMap.values.toList();
            failedRefs.remove('reporters');
          }
        }
      } catch (_) {
        // Keep original failedRefs markers for required endpoints.
      }
    }

    if (!mounted) return;

    setState(() {
      _properties = props
          .cast<Map<String, dynamic>>()
          .where((p) => p['id'] != null && p['name'] != null)
          .toList();
      _categories = cats
          .cast<Map<String, dynamic>>()
          .where((c) => c['id'] != null && c['name'] != null)
          .toList();
      _technicians = technicians
          .cast<Map<String, dynamic>>()
          .where((t) => t['id'] != null && t['name'] != null)
          .toList();
      _reporters = reporters
          .cast<Map<String, dynamic>>()
          .where((r) => r['id'] != null && r['name'] != null)
          .toList();

      if (_reporters.isNotEmpty) {
        final uniqueReporters = <int, Map<String, dynamic>>{};
        for (final reporter in _reporters) {
          final id = _asInt(reporter['id']);
          final name = reporter['name'] as String?;
          if (id != null && name != null) {
            uniqueReporters[id] = {'id': id, 'name': name};
          }
        }
        _reporters = uniqueReporters.values.toList();
      }

      if (_technicians.isNotEmpty) {
        failedRefs.remove('technicians');
      }
      if (_reporters.isNotEmpty) {
        failedRefs.remove('reporters');
      }

      if (!_isReporterScopedRole && _reporters.isEmpty && currentUser != null) {
        _reporters = [
          {'id': currentUser.id, 'name': currentUser.name}
        ];
      }

      if (ticketData != null) {
        _titleCtrl.text = ticketData['title'] as String? ?? '';
        _descCtrl.text = ticketData['description'] as String? ?? '';
        _unitCtrl.text = ticketData['unit'] as String? ?? '';
        _propertyId = _asInt(ticketData['property']?['id']);
        _categoryId = _asInt(ticketData['category']?['id']);
        _assignedTo = _asInt(ticketData['technician']?['id']);
        _reportedBy = _asInt(ticketData['reporter']?['id']);
        _priority = ticketData['priority'] as String? ?? 'medium';
        _etd = DateTime.tryParse(ticketData['etd'] as String? ?? '');
        _estimatedCostCtrl.text = ticketData['estimated_cost'] as String? ?? '';
        _estimatedCostCurrency =
            (ticketData['estimated_cost_currency'] as String?) ?? 'GHS';
      } else if (!_isReporterScopedRole && _reporters.isNotEmpty) {
        _reportedBy = _asInt(_reporters.first['id']);
      }

      if (!_isReporterScopedRole && _reporters.isNotEmpty) {
        final hasSelectedReporter =
            _reporters.any((r) => _asInt(r['id']) == _reportedBy);
        if (!hasSelectedReporter) {
          _reportedBy = _asInt(_reporters.first['id']);
        }
      } else if (!_isReporterScopedRole && currentUser != null) {
        _reportedBy = currentUser.id;
      }

      final failedCritical = _properties.isEmpty || _categories.isEmpty;
      if (failedCritical) {
        final missingRequired = [
          if (_properties.isEmpty) 'properties',
          if (_categories.isEmpty) 'categories',
        ];
        _error =
            'Unable to load required dropdowns (${missingRequired.join(', ')}). Please retry.';
      } else if (failedRefs.isNotEmpty) {
        _error =
            'Some optional references failed to load (${failedRefs.join(', ')}), but you can continue.';
      } else {
        _error = null;
      }
    });

    if (mounted) {
      setState(() => _loading = false);
    }
  }

  Future<void> _pickGalleryImages() async {
    final files = await _imagePicker.pickMultiImage(imageQuality: 85, maxWidth: 1600);
    if (files.isEmpty) return;
    setState(() {
      for (final file in files) {
        _imageAttachmentPaths.add(file.path);
      }
    });
  }

  Future<void> _pickCameraImage() async {
    final file = await _imagePicker.pickImage(
      source: ImageSource.camera,
      imageQuality: 85,
      maxWidth: 1600,
    );
    if (file == null) return;
    setState(() => _cameraAttachmentPath = file.path);
  }

  Future<void> _pickDocuments() async {
    final result = await FilePicker.platform.pickFiles(
      allowMultiple: true,
      type: FileType.custom,
      allowedExtensions: ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'csv'],
    );
    if (result == null) return;
    final paths = result.files.map((f) => f.path).whereType<String>().toList();
    setState(() => _documentAttachmentPaths.addAll(paths));
  }

  Future<void> _pickEtd() async {
    final now = DateTime.now();
    final start = _etd ?? now;

    final date = await showDatePicker(
      context: context,
      firstDate: DateTime(now.year - 1),
      lastDate: DateTime(now.year + 5),
      initialDate: start,
    );
    if (date == null || !mounted) return;

    final time = await showTimePicker(
      context: context,
      initialTime: TimeOfDay.fromDateTime(start),
    );
    if (time == null || !mounted) return;

    setState(() {
      _etd = DateTime(
        date.year,
        date.month,
        date.day,
        time.hour,
        time.minute,
      );
    });
  }

  String _formatEtd(DateTime? value) {
    if (value == null) return 'Select date and time';
    final mm = value.month.toString().padLeft(2, '0');
    final dd = value.day.toString().padLeft(2, '0');
    final hh = value.hour.toString().padLeft(2, '0');
    final min = value.minute.toString().padLeft(2, '0');
    return '${value.year}-$mm-$dd $hh:$min';
  }

  Future<void> _uploadAttachments(int ticketId) async {
    final attachmentPaths = <Map<String, String>>[];
    for (final p in _imageAttachmentPaths) {
      attachmentPaths.add({'path': p, 'type': 'image'});
    }
    if (_cameraAttachmentPath != null) {
      attachmentPaths.add({'path': _cameraAttachmentPath!, 'type': 'image'});
    }
    for (final p in _documentAttachmentPaths) {
      attachmentPaths.add({'path': p, 'type': 'document'});
    }

    for (final attachment in attachmentPaths) {
      await ApiService.instance.uploadTicketAttachment(
        ticketId: ticketId,
        filePath: attachment['path']!,
        attachmentType: attachment['type'],
      );
    }
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    if (_propertyId == null) {
      setState(() => _error = 'Please select a property.');
      return;
    }

    if (_categoryId == null) {
      setState(() => _error = 'Please select a category.');
      return;
    }

    if (!_isReporterScopedRole && !_isEditMode && _reportedBy == null) {
      final currentUser = context.read<AuthProvider>().user;
      if (currentUser != null) {
        _reportedBy = currentUser.id;
      }
    }

    if (!_isReporterScopedRole && !_isEditMode && _reportedBy == null) {
      setState(() => _error = 'Please select a reporter.');
      return;
    }

    final estimatedCostRaw = _estimatedCostCtrl.text.trim();
    final estimatedCost =
        estimatedCostRaw.isEmpty ? null : double.tryParse(estimatedCostRaw);

    if (estimatedCostRaw.isNotEmpty && estimatedCost == null) {
      setState(() => _error = 'Estimated cost must be a valid number.');
      return;
    }

    setState(() {
      _submitting = true;
      _error = null;
    });

    final selectedAttachmentCount =
      _imageAttachmentPaths.length +
      _documentAttachmentPaths.length +
      (_cameraAttachmentPath != null ? 1 : 0);

    final payload = {
      'title': _titleCtrl.text.trim(),
      'description': _descCtrl.text.trim(),
      'property_id': _propertyId,
      'maintenance_category_id': _categoryId,
      'unit': _unitCtrl.text.trim().isEmpty ? null : _unitCtrl.text.trim(),
      'priority': _priority,
      'etd': _etd?.toIso8601String(),
      'estimated_cost': estimatedCost,
      'estimated_cost_currency':
          estimatedCost == null ? null : _estimatedCostCurrency,
      if (!_isReporterScopedRole && !_isEditMode) 'reported_by': _reportedBy,
      if (!_isEditMode) 'assigned_to': _assignedTo,
    };

    final ticketProvider = context.read<TicketProvider>();
    final success = _isEditMode
        ? await ticketProvider.updateTicket(widget.ticketId!, payload)
        : await ticketProvider.createTicket(payload);

    if (success && !_isEditMode) {
      final createdTicketId = ticketProvider.lastCreatedTicketId;
      if (createdTicketId != null) {
        try {
          await _uploadAttachments(createdTicketId);
        } catch (_) {
          if (mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(
                content: Text('Task created, but some attachments failed to upload.'),
                backgroundColor: AppColors.error,
              ),
            );
          }
        }
      }
    }

    if (!mounted) return;

    setState(() => _submitting = false);

    if (success) {
      if (_isEditMode) {
        context.go('/tasks/${widget.ticketId}');
      } else {
        final createdTicketId = ticketProvider.lastCreatedTicketId;
        context.go(
          '/task-success',
          extra: {
            'ticketNo': ticketProvider.lastCreatedTicketNo ?? '',
            'message': ticketProvider.lastCreateMessage,
            'ticketId': createdTicketId,
            'attachmentCount': selectedAttachmentCount,
          },
        );
      }
    } else {
      setState(
        () => _error = ticketProvider.error ??
            (_isEditMode ? 'Failed to update task.' : 'Failed to create task.'),
      );
    }
  }

  @override
  void dispose() {
    _titleCtrl.dispose();
    _descCtrl.dispose();
    _unitCtrl.dispose();
    _estimatedCostCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final user = context.watch<AuthProvider>().user;

    if (!_isEditMode && !(user?.canCreateTickets ?? false)) {
      return Scaffold(
        backgroundColor: AppColors.background,
        body: Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Icon(Icons.lock_outline, color: AppColors.error, size: 40),
                const SizedBox(height: 12),
                const Text(
                  'You do not have permission to log a new task.',
                  textAlign: TextAlign.center,
                  style: TextStyle(color: AppColors.textSecondary, fontSize: 14),
                ),
                const SizedBox(height: 16),
                ElevatedButton(
                  onPressed: () => context.go('/tasks'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.primary,
                  ),
                  child: const Text(
                    'Back to Tasks',
                    style: TextStyle(color: Colors.white),
                  ),
                ),
              ],
            ),
          ),
        ),
      );
    }

    return Scaffold(
      backgroundColor: AppColors.background,
      body: Column(
        children: [
          Container(
            width: double.infinity,
            decoration: const BoxDecoration(
              gradient: LinearGradient(
                colors: [AppColors.primaryDark, AppColors.primary],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
            ),
            child: SafeArea(
              bottom: false,
              child: Padding(
                padding: const EdgeInsets.fromLTRB(16, 12, 16, 20),
                child: Row(
                  children: [
                    GestureDetector(
                      onTap: () => context.go('/tasks'),
                      child: const Icon(
                        Icons.arrow_back_ios,
                        color: Colors.white,
                        size: 20,
                      ),
                    ),
                    const SizedBox(width: 10),
                    Text(
                      _isEditMode ? 'Edit Task' : 'Log New Task',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 20,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
          Expanded(
            child: _loading
                ? const Center(
                    child: CircularProgressIndicator(color: AppColors.primary),
                  )
                : SingleChildScrollView(
                    padding: const EdgeInsets.all(20),
                    child: Form(
                      key: _formKey,
                      child: Column(
                        children: [
                          if (_error != null)
                            Container(
                              width: double.infinity,
                              padding: const EdgeInsets.all(12),
                              margin: const EdgeInsets.only(bottom: 16),
                              decoration: BoxDecoration(
                                color: AppColors.error.withValues(alpha: 0.1),
                                borderRadius: BorderRadius.circular(10),
                              ),
                              child: Text(
                                _error!,
                                style: const TextStyle(
                                  color: AppColors.error,
                                  fontSize: 13,
                                ),
                              ),
                            ),
                          _field(
                            _titleCtrl,
                            'Task Title',
                            Icons.title_outlined,
                            validator: (v) =>
                                v!.trim().isEmpty ? 'Enter a title' : null,
                          ),
                          const SizedBox(height: 14),
                          TextFormField(
                            controller: _descCtrl,
                            maxLines: 4,
                            validator: (v) =>
                                v!.trim().isEmpty ? 'Enter a description' : null,
                            decoration: _decor(
                              'Description',
                              Icons.description_outlined,
                            ),
                          ),
                          const SizedBox(height: 14),
                          DropdownButtonFormField<int>(
                            initialValue: _propertyId,
                            hint: const Text(
                              'Select Property',
                              style: TextStyle(
                                color: AppColors.textLight,
                                fontSize: 14,
                              ),
                            ),
                            items: _properties
                                .map(
                                  (p) => DropdownMenuItem<int>(
                                    value: _asInt(p['id']),
                                    child: Text((p['name'] as String?) ?? 'Property'),
                                  ),
                                )
                                .toList(),
                            onChanged: (v) => setState(() => _propertyId = v),
                            decoration: _decor(
                              'Property',
                              Icons.location_on_outlined,
                            ),
                          ),
                          const SizedBox(height: 14),
                          DropdownButtonFormField<int>(
                            initialValue: _categoryId,
                            hint: const Text(
                              'Select Category',
                              style: TextStyle(
                                color: AppColors.textLight,
                                fontSize: 14,
                              ),
                            ),
                            items: _categories
                                .map(
                                  (c) => DropdownMenuItem<int>(
                                    value: _asInt(c['id']),
                                    child: Text((c['name'] as String?) ?? 'Category'),
                                  ),
                                )
                                .toList(),
                            onChanged: (v) => setState(() => _categoryId = v),
                            decoration: _decor(
                              'Category',
                              Icons.category_outlined,
                            ),
                          ),
                          const SizedBox(height: 14),
                          _field(
                            _unitCtrl,
                            'Unit / Room (Optional)',
                            Icons.door_back_door_outlined,
                          ),
                          const SizedBox(height: 14),
                          if (!_isReporterScopedRole && !_isEditMode) ...[
                            DropdownButtonFormField<int>(
                              initialValue: _reportedBy,
                              hint: const Text(
                                'Select Reporter',
                                style: TextStyle(
                                  color: AppColors.textLight,
                                  fontSize: 14,
                                ),
                              ),
                              items: _reporters
                                  .map(
                                    (r) => DropdownMenuItem<int>(
                                      value: _asInt(r['id']),
                                      child: Text((r['name'] as String?) ?? 'User'),
                                    ),
                                  )
                                  .toList(),
                              onChanged: (v) => setState(() => _reportedBy = v),
                              decoration: _decor('Reporter', Icons.person_outline),
                            ),
                            const SizedBox(height: 14),
                            DropdownButtonFormField<int>(
                              initialValue: _assignedTo,
                              hint: const Text(
                                'Assign Technician (Optional)',
                                style: TextStyle(
                                  color: AppColors.textLight,
                                  fontSize: 14,
                                ),
                              ),
                              items: [
                                const DropdownMenuItem<int>(
                                  value: null,
                                  child: Text('Unassigned'),
                                ),
                                ..._technicians.map(
                                  (t) => DropdownMenuItem<int>(
                                    value: _asInt(t['id']),
                                    child: Text((t['name'] as String?) ?? 'Technician'),
                                  ),
                                ),
                              ],
                              onChanged: (v) => setState(() => _assignedTo = v),
                              decoration:
                                  _decor('Assigned Technician', Icons.engineering),
                            ),
                            const SizedBox(height: 14),
                          ],
                          DropdownButtonFormField<String>(
                            initialValue: _priority,
                            items: ['low', 'medium', 'high', 'urgent']
                                .map(
                                  (p) => DropdownMenuItem(
                                    value: p,
                                    child: Row(
                                      children: [
                                        Icon(
                                          Icons.flag,
                                          color: AppColors.priorityColor(p),
                                          size: 16,
                                        ),
                                        const SizedBox(width: 6),
                                        Text(
                                          p.toUpperCase(),
                                          style: TextStyle(
                                            color: AppColors.priorityColor(p),
                                            fontSize: 13,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                )
                                .toList(),
                            onChanged: (v) =>
                                setState(() => _priority = v ?? 'medium'),
                            decoration: _decor('Priority', Icons.flag_outlined),
                          ),
                          const SizedBox(height: 14),
                          InkWell(
                            onTap: _pickEtd,
                            borderRadius: BorderRadius.circular(12),
                            child: InputDecorator(
                              decoration:
                                  _decor('Expected Completion Date & Time', Icons.event),
                              child: Row(
                                children: [
                                  Expanded(
                                    child: Text(
                                      _formatEtd(_etd),
                                      style: TextStyle(
                                        color: _etd == null
                                            ? AppColors.textLight
                                            : AppColors.textPrimary,
                                      ),
                                    ),
                                  ),
                                  if (_etd != null)
                                    IconButton(
                                      onPressed: () => setState(() => _etd = null),
                                      icon: const Icon(
                                        Icons.clear,
                                        size: 18,
                                        color: AppColors.textSecondary,
                                      ),
                                    ),
                                ],
                              ),
                            ),
                          ),
                          const SizedBox(height: 14),
                          Row(
                            children: [
                              Expanded(
                                flex: 3,
                                child: DropdownButtonFormField<String>(
                                  initialValue: _estimatedCostCurrency,
                                  items: _currencies
                                      .map(
                                        (currency) => DropdownMenuItem(
                                          value: currency,
                                          child: Text(currency),
                                        ),
                                      )
                                      .toList(),
                                  onChanged: (value) => setState(
                                    () => _estimatedCostCurrency = value ?? 'GHS',
                                  ),
                                  decoration: _decor('Currency', Icons.currency_exchange),
                                ),
                              ),
                              const SizedBox(width: 10),
                              Expanded(
                                flex: 5,
                                child: TextFormField(
                                  controller: _estimatedCostCtrl,
                                  keyboardType: const TextInputType.numberWithOptions(
                                    decimal: true,
                                  ),
                                  decoration:
                                      _decor('Estimated Cost (Optional)', Icons.payments),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 18),
                          _buildAttachmentsSection(),
                          const SizedBox(height: 28),
                          SizedBox(
                            width: double.infinity,
                            height: 52,
                            child: ElevatedButton(
                              onPressed: _submitting ? null : _submit,
                              style: ElevatedButton.styleFrom(
                                backgroundColor: AppColors.primary,
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(14),
                                ),
                                elevation: 0,
                              ),
                              child: _submitting
                                  ? const CircularProgressIndicator(
                                      color: Colors.white,
                                      strokeWidth: 2,
                                    )
                                  : Text(
                                      _isEditMode ? 'Update Task' : 'Submit Task',
                                      style: const TextStyle(
                                        color: Colors.white,
                                        fontSize: 16,
                                        fontWeight: FontWeight.w600,
                                      ),
                                    ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
          ),
        ],
      ),
    );
  }

  Widget _buildAttachmentsSection() {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.divider),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Attachments',
            style: TextStyle(
              color: AppColors.textPrimary,
              fontSize: 15,
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 10),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              OutlinedButton.icon(
                onPressed: _pickGalleryImages,
                icon: const Icon(Icons.image_outlined, size: 16),
                label: const Text('Upload Pictures'),
              ),
              OutlinedButton.icon(
                onPressed: _pickCameraImage,
                icon: const Icon(Icons.photo_camera_outlined, size: 16),
                label: const Text('Take Picture'),
              ),
              OutlinedButton.icon(
                onPressed: _pickDocuments,
                icon: const Icon(Icons.attach_file_outlined, size: 16),
                label: const Text('Upload Documents'),
              ),
            ],
          ),
          const SizedBox(height: 10),
          if (_imageAttachmentPaths.isNotEmpty)
            _buildPathList('Pictures', _imageAttachmentPaths, (idx) {
              setState(() => _imageAttachmentPaths.removeAt(idx));
            }),
          if (_cameraAttachmentPath != null)
            _buildPathList('Camera', [_cameraAttachmentPath!], (idx) {
              setState(() => _cameraAttachmentPath = null);
            }),
          if (_documentAttachmentPaths.isNotEmpty)
            _buildPathList('Documents', _documentAttachmentPaths, (idx) {
              setState(() => _documentAttachmentPaths.removeAt(idx));
            }),
          if (_imageAttachmentPaths.isEmpty &&
              _cameraAttachmentPath == null &&
              _documentAttachmentPaths.isEmpty)
            const Text(
              'No files selected.',
              style: TextStyle(color: AppColors.textSecondary, fontSize: 12),
            ),
        ],
      ),
    );
  }

  Widget _buildPathList(
    String label,
    List<String> paths,
    void Function(int) onRemove,
  ) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: const TextStyle(
            color: AppColors.textSecondary,
            fontSize: 12,
            fontWeight: FontWeight.w600,
          ),
        ),
        const SizedBox(height: 6),
        ...paths.asMap().entries.map(
          (entry) {
            final index = entry.key;
            final fileName = entry.value.split(RegExp(r'[\\/]')).last;
            return Container(
              margin: const EdgeInsets.only(bottom: 6),
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
              decoration: BoxDecoration(
                color: AppColors.background,
                borderRadius: BorderRadius.circular(8),
              ),
              child: Row(
                children: [
                  Expanded(
                    child: Text(
                      fileName,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        color: AppColors.textPrimary,
                        fontSize: 12,
                      ),
                    ),
                  ),
                  IconButton(
                    onPressed: () => onRemove(index),
                    icon: const Icon(
                      Icons.close,
                      size: 16,
                      color: AppColors.error,
                    ),
                  ),
                ],
              ),
            );
          },
        ),
      ],
    );
  }

  Widget _field(
    TextEditingController ctrl,
    String label,
    IconData icon, {
    String? Function(String?)? validator,
  }) {
    return TextFormField(
      controller: ctrl,
      validator: validator,
      decoration: _decor(label, icon),
    );
  }

  InputDecoration _decor(String label, IconData icon) {
    return InputDecoration(
      labelText: label,
      prefixIcon: Icon(icon, color: AppColors.primary, size: 20),
      filled: true,
      fillColor: Colors.white,
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide.none,
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(color: AppColors.divider.withValues(alpha: 0.8)),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: const BorderSide(color: AppColors.primary, width: 1.5),
      ),
      labelStyle: const TextStyle(color: AppColors.textSecondary, fontSize: 14),
    );
  }
}
