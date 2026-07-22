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

  static const _fieldSpacing = 16.0;
  static const _sectionSpacing = 24.0;

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
                const Icon(Icons.lock_outline, color: AppColors.error, size: 48),
                const SizedBox(height: 16),
                const Text(
                  'You do not have permission to log a new task.',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    color: AppColors.textSecondary,
                    fontSize: 15,
                    height: 1.5,
                  ),
                ),
                const SizedBox(height: 24),
                SizedBox(
                  width: double.infinity,
                  height: 48,
                  child: ElevatedButton(
                    onPressed: () => context.go('/tasks'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.primary,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                      elevation: 0,
                    ),
                    child: const Text(
                      'Back to Tasks',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 15,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
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
                padding: const EdgeInsets.fromLTRB(16, 12, 16, 18),
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
                    const SizedBox(width: 12),
                    Expanded(
                      child: Text(
                        _isEditMode ? 'Edit Task' : 'Log New Task',
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 20,
                          fontWeight: FontWeight.bold,
                          letterSpacing: 0.3,
                        ),
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
                    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 24),
                    child: Form(
                      key: _formKey,
                      child: Column(
                        children: [
                          if (_error != null)
                            Container(
                              width: double.infinity,
                              padding: const EdgeInsets.all(14),
                              margin: const EdgeInsets.only(bottom: _sectionSpacing),
                              decoration: BoxDecoration(
                                color: AppColors.error.withValues(alpha: 0.08),
                                border: Border.all(
                                  color: AppColors.error.withValues(alpha: 0.3),
                                ),
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: Row(
                                children: [
                                  const Icon(
                                    Icons.error_outline,
                                    color: AppColors.error,
                                    size: 20,
                                  ),
                                  const SizedBox(width: 10),
                                  Expanded(
                                    child: Text(
                                      _error!,
                                      style: const TextStyle(
                                        color: AppColors.error,
                                        fontSize: 13,
                                        height: 1.4,
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          _buildBasicInfoSection(),
                          const SizedBox(height: _sectionSpacing),
                          _buildDetailsSection(),
                          const SizedBox(height: _sectionSpacing),
                          if (!_isReporterScopedRole && !_isEditMode)
                            ...[
                              _buildAssignmentSection(),
                              const SizedBox(height: _sectionSpacing),
                            ],
                          _buildAttachmentsSection(),
                          const SizedBox(height: _sectionSpacing),
                          _buildSubmitButton(),
                          const SizedBox(height: 24),
                        ],
                      ),
                    ),
                  ),
          ),
        ],
      ),
    );
  }

  Widget _buildBasicInfoSection() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Basic Information',
          style: TextStyle(
            color: AppColors.textPrimary,
            fontSize: 15,
            fontWeight: FontWeight.w700,
            letterSpacing: 0.3,
          ),
        ),
        const SizedBox(height: 14),
        _field(
          _titleCtrl,
          'Task Title',
          Icons.title_outlined,
          validator: (v) => v!.trim().isEmpty ? 'Enter a title' : null,
        ),
        const SizedBox(height: _fieldSpacing),
        TextFormField(
          controller: _descCtrl,
          maxLines: 4,
          minLines: 3,
          validator: (v) =>
              v!.trim().isEmpty ? 'Enter a description' : null,
          decoration: _decor(
            'Description',
            Icons.description_outlined,
          ),
        ),
      ],
    );
  }

  Widget _buildDetailsSection() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Task Details',
          style: TextStyle(
            color: AppColors.textPrimary,
            fontSize: 15,
            fontWeight: FontWeight.w700,
            letterSpacing: 0.3,
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
        const SizedBox(height: _fieldSpacing),
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
        const SizedBox(height: _fieldSpacing),
        _field(
          _unitCtrl,
          'Unit / Room',
          Icons.door_back_door_outlined,
        ),
        const SizedBox(height: _fieldSpacing),
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
                      const SizedBox(width: 8),
                      Text(
                        p.toUpperCase(),
                        style: TextStyle(
                          color: AppColors.priorityColor(p),
                          fontSize: 13,
                          fontWeight: FontWeight.w600,
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
        const SizedBox(height: _fieldSpacing),
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
                      fontSize: 14,
                    ),
                  ),
                ),
                if (_etd != null)
                  IconButton(
                    onPressed: () => setState(() => _etd = null),
                    icon: const Icon(
                      Icons.close,
                      size: 18,
                      color: AppColors.textSecondary,
                    ),
                  ),
              ],
            ),
          ),
        ),
        const SizedBox(height: _fieldSpacing),
        _buildCostField(),
      ],
    );
  }

  Widget _buildAssignmentSection() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Assignment',
          style: TextStyle(
            color: AppColors.textPrimary,
            fontSize: 15,
            fontWeight: FontWeight.w700,
            letterSpacing: 0.3,
          ),
        ),
        const SizedBox(height: 14),
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
        const SizedBox(height: _fieldSpacing),
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
      ],
    );
  }

  Widget _buildCostField() {
    return Row(
      children: [
        SizedBox(
          width: 100,
          child: DropdownButtonFormField<String>(
            initialValue: _estimatedCostCurrency,
            items: _currencies
                .map(
                  (currency) => DropdownMenuItem(
                    value: currency,
                    child: Text(
                      currency,
                      style: const TextStyle(fontSize: 13),
                    ),
                  ),
                )
                .toList(),
            onChanged: (value) => setState(
              () => _estimatedCostCurrency = value ?? 'GHS',
            ),
            decoration: _decor('', Icons.currency_exchange),
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
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
    );
  }

  Widget _buildSubmitButton() {
    return SizedBox(
      width: double.infinity,
      height: 50,
      child: ElevatedButton(
        onPressed: _submitting ? null : _submit,
        style: ElevatedButton.styleFrom(
          backgroundColor: AppColors.primary,
          disabledBackgroundColor: AppColors.primary
              .withValues(alpha: 0.6),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
          elevation: 2,
        ),
        child: _submitting
            ? const SizedBox(
                height: 24,
                width: 24,
                child: CircularProgressIndicator(
                  color: Colors.white,
                  strokeWidth: 2.5,
                ),
              )
            : Text(
                _isEditMode ? 'Update Task' : 'Submit Task',
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 15,
                  fontWeight: FontWeight.w600,
                  letterSpacing: 0.3,
                ),
              ),
      ),
    );
  }

  @override
  void dispose() {
    _titleCtrl.dispose();
    _descCtrl.dispose();
    _unitCtrl.dispose();
    _estimatedCostCtrl.dispose();
    super.dispose();
  }

  Widget _buildAttachmentsSection() {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.divider, width: 1),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
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
              letterSpacing: 0.3,
            ),
          ),
          const SizedBox(height: 12),
          Wrap(
            spacing: 10,
            runSpacing: 10,
            children: [
              SizedBox(
                height: 36,
                child: OutlinedButton.icon(
                  onPressed: _pickGalleryImages,
                  icon: const Icon(Icons.image_outlined, size: 18),
                  label: const Text('Pictures'),
                  style: OutlinedButton.styleFrom(
                    side: const BorderSide(color: AppColors.primary),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(8),
                    ),
                    padding: const EdgeInsets.symmetric(horizontal: 12),
                  ),
                ),
              ),
              SizedBox(
                height: 36,
                child: OutlinedButton.icon(
                  onPressed: _pickCameraImage,
                  icon: const Icon(Icons.photo_camera_outlined, size: 18),
                  label: const Text('Camera'),
                  style: OutlinedButton.styleFrom(
                    side: const BorderSide(color: AppColors.primary),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(8),
                    ),
                    padding: const EdgeInsets.symmetric(horizontal: 12),
                  ),
                ),
              ),
              SizedBox(
                height: 36,
                child: OutlinedButton.icon(
                  onPressed: _pickDocuments,
                  icon: const Icon(Icons.attach_file_outlined, size: 18),
                  label: const Text('Documents'),
                  style: OutlinedButton.styleFrom(
                    side: const BorderSide(color: AppColors.primary),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(8),
                    ),
                    padding: const EdgeInsets.symmetric(horizontal: 12),
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          if (_imageAttachmentPaths.isNotEmpty)
            _buildAttachmentGroup('Pictures', _imageAttachmentPaths, (idx) {
              setState(() => _imageAttachmentPaths.removeAt(idx));
            }),
          if (_cameraAttachmentPath != null) ...[
            if (_imageAttachmentPaths.isNotEmpty) const SizedBox(height: 10),
            _buildAttachmentGroup('Camera', [_cameraAttachmentPath!], (idx) {
              setState(() => _cameraAttachmentPath = null);
            }),
          ],
          if (_documentAttachmentPaths.isNotEmpty) ...[
            if (_imageAttachmentPaths.isNotEmpty ||
                _cameraAttachmentPath != null)
              const SizedBox(height: 10),
            _buildAttachmentGroup('Documents', _documentAttachmentPaths, (idx) {
              setState(() => _documentAttachmentPaths.removeAt(idx));
            }),
          ],
          if (_imageAttachmentPaths.isEmpty &&
              _cameraAttachmentPath == null &&
              _documentAttachmentPaths.isEmpty)
            const Padding(
              padding: EdgeInsets.only(top: 8),
              child: Text(
                'No files selected',
                style: TextStyle(
                  color: AppColors.textSecondary,
                  fontSize: 13,
                  fontStyle: FontStyle.italic,
                ),
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildAttachmentGroup(
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
            letterSpacing: 0.2,
          ),
        ),
        const SizedBox(height: 8),
        ...paths.asMap().entries.map(
          (entry) {
            final index = entry.key;
            final fileName = entry.value.split(RegExp(r'[\\/]')).last;
            return Container(
              margin: const EdgeInsets.only(bottom: 6),
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
              decoration: BoxDecoration(
                color: AppColors.background,
                borderRadius: BorderRadius.circular(8),
                border: Border.all(
                  color: AppColors.divider.withValues(alpha: 0.5),
                ),
              ),
              child: Row(
                children: [
                  Icon(
                    _getFileIcon(fileName),
                    size: 18,
                    color: AppColors.primary.withValues(alpha: 0.7),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          fileName,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            color: AppColors.textPrimary,
                            fontSize: 12,
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 8),
                  SizedBox(
                    width: 32,
                    height: 32,
                    child: IconButton(
                      onPressed: () => onRemove(index),
                      padding: EdgeInsets.zero,
                      icon: const Icon(
                        Icons.close,
                        size: 16,
                        color: AppColors.error,
                      ),
                      tooltip: 'Remove',
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

  IconData _getFileIcon(String fileName) {
    if (fileName.endsWith('.pdf')) return Icons.picture_as_pdf;
    if (fileName.endsWith('.doc') || fileName.endsWith('.docx'))
      return Icons.description;
    if (fileName.endsWith('.xls') || fileName.endsWith('.xlsx'))
      return Icons.table_chart;
    return Icons.insert_drive_file;
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
