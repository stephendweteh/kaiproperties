import 'package:flutter/material.dart';
import 'package:dio/dio.dart';
import 'package:file_picker/file_picker.dart';
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';
import '../../core/constants/app_colors.dart';
import '../../core/models/user_model.dart';
import '../../core/providers/ticket_provider.dart';
import '../../core/providers/auth_provider.dart';
import '../../core/models/ticket_model.dart';
import '../../core/services/api_service.dart';
import '../../core/widgets/status_badge.dart';

String _formatCreatedAt(String? value) {
  final createdAt = value == null ? null : DateTime.tryParse(value)?.toLocal();
  if (createdAt == null) {
    return 'N/A';
  }

  return DateFormat('MMM d, yyyy h:mm a').format(createdAt);
}

String _formatDateTime(String? value) {
  final parsed = value == null ? null : DateTime.tryParse(value)?.toLocal();
  if (parsed == null) {
    return 'N/A';
  }

  return DateFormat('MMM d, yyyy h:mm a').format(parsed);
}

class TaskDetailScreen extends StatefulWidget {
  final int ticketId;
  const TaskDetailScreen({super.key, required this.ticketId});

  @override
  State<TaskDetailScreen> createState() => _TaskDetailScreenState();
}

class _TaskDetailScreenState extends State<TaskDetailScreen> {
  List<Map<String, dynamic>> _technicians = [];
  List<Map<String, dynamic>> _phases = [];
  int? _selectedTechnicianId;
  bool _assigning = false;
  bool _loadingPhases = false;
  bool _savingPhase = false;
  final ImagePicker _imagePicker = ImagePicker();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<TicketProvider>().loadTicket(widget.ticketId);
      _loadTechnicians();
      _loadPhases();
    });
  }

  Future<void> _loadTechnicians() async {
    try {
      final items = await ApiService.instance.getTechnicians();
      if (!mounted) return;
      setState(() {
        _technicians = items.whereType<Map<String, dynamic>>().toList(
          growable: false,
        );
      });
    } catch (_) {}
  }

  Future<void> _loadPhases() async {
    if (!mounted) return;
    setState(() => _loadingPhases = true);
    try {
      final data = await ApiService.instance.getTicketPhases(widget.ticketId);
      final rawItems = (data['data'] as List<dynamic>? ?? const []);

      if (!mounted) return;
      setState(() {
        _phases = rawItems
            .whereType<Map<String, dynamic>>()
            .toList(growable: false);
      });
    } catch (_) {
      if (!mounted) return;
      setState(() => _phases = []);
    } finally {
      if (mounted) {
        setState(() => _loadingPhases = false);
      }
    }
  }

  Future<void> _refreshTicketData() async {
    await context.read<TicketProvider>().loadTicket(widget.ticketId);
    await _loadPhases();
  }

  @override
  Widget build(BuildContext context) {
    final prov = context.watch<TicketProvider>();
    final ticket = prov.selected;
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
                    const Expanded(
                      child: Text(
                        'Task Details',
                        style: TextStyle(
                          color: Colors.white,
                          fontSize: 20,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                    if (ticket != null) StatusBadge(status: ticket.status),
                  ],
                ),
              ),
            ),
          ),
          Expanded(
            child: prov.loading
                ? const Center(
                    child: CircularProgressIndicator(color: AppColors.primary),
                  )
                : ticket == null
                ? const Center(
                    child: Text(
                      'Task not found',
                      style: TextStyle(color: AppColors.textSecondary),
                    ),
                  )
                : _buildDetail(context, ticket, prov),
          ),
        ],
      ),
    );
  }

  Widget _buildDetail(
    BuildContext context,
    TicketModel ticket,
    TicketProvider prov,
  ) {
    final user = context.read<AuthProvider>().user!;
    return RefreshIndicator(
      color: AppColors.primary,
      onRefresh: _refreshTicketData,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // Ticket number & title card
          _InfoCard(
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(
                      ticket.ticketNo,
                      style: const TextStyle(
                        color: AppColors.textSecondary,
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
                        letterSpacing: 0.8,
                      ),
                    ),
                  ),
                  if (user.canEditTicket(
                    reportedById: ticket.reporter?.id,
                    status: ticket.status,
                  ))
                    TextButton.icon(
                      onPressed: () => context.go('/tasks/${ticket.id}/edit'),
                      icon: const Icon(Icons.edit_outlined, size: 16),
                      label: const Text('Edit'),
                      style: TextButton.styleFrom(
                        foregroundColor: AppColors.primary,
                        padding: const EdgeInsets.symmetric(
                          horizontal: 10,
                          vertical: 6,
                        ),
                      ),
                    ),
                ],
              ),
              const SizedBox(height: 6),
              Text(
                ticket.title,
                style: const TextStyle(
                  color: AppColors.textPrimary,
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  StatusBadge(status: ticket.status),
                  const SizedBox(width: 10),
                  PriorityBadge(priority: ticket.priority),
                ],
              ),
            ],
          ),
          const SizedBox(height: 12),
          // Description
          _InfoCard(
            children: [
              const Text(
                'Description',
                style: TextStyle(
                  color: AppColors.textSecondary,
                  fontSize: 12,
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                ticket.description,
                style: const TextStyle(
                  color: AppColors.textPrimary,
                  fontSize: 14,
                  height: 1.5,
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          // Details
          _InfoCard(
            children: [
              _DetailRow(
                Icons.location_on_outlined,
                'Property',
                ticket.property?.name ?? 'N/A',
              ),
              const Divider(color: AppColors.divider, height: 16),
              _DetailRow(
                Icons.category_outlined,
                'Category',
                ticket.category?.name ?? 'N/A',
              ),
              if (ticket.unit != null) ...[
                const Divider(color: AppColors.divider, height: 16),
                _DetailRow(Icons.door_back_door_outlined, 'Unit', ticket.unit!),
              ],
              const Divider(color: AppColors.divider, height: 16),
              _DetailRow(
                Icons.person_outline,
                'Reported By',
                ticket.reporter?.name ?? 'N/A',
              ),
              const Divider(color: AppColors.divider, height: 16),
              _DetailRow(
                Icons.schedule_outlined,
                'Created',
                _formatCreatedAt(ticket.createdAt),
              ),
              if (ticket.technician != null) ...[
                const Divider(color: AppColors.divider, height: 16),
                _DetailRow(
                  Icons.engineering_outlined,
                  'Technician',
                  ticket.technician!.name,
                ),
              ],
              if (ticket.etd != null) ...[
                const Divider(color: AppColors.divider, height: 16),
                _DetailRow(
                  Icons.event_outlined,
                  'Due Date',
                  ticket.etd!.substring(0, 10),
                ),
              ],
            ],
          ),
          const SizedBox(height: 12),
          _buildWorkProgressSection(context, ticket, user, prov),
          const SizedBox(height: 12),
          // Status actions
          _buildActions(context, ticket, user, prov),
          const SizedBox(height: 24),
        ],
      ),
    );
  }

  Widget _buildActions(
    BuildContext context,
    TicketModel ticket,
    user,
    TicketProvider prov,
  ) {
    final canAssign = user.isOperationsManager || user.isAdmin;
    final isTech = user.isTechnician;
    final isOwned = ticket.technician?.id == user.id;

    final List<Widget> widgets = [];
    final List<(String, String, Color)> actions = [];

    if (canAssign && _technicians.isNotEmpty) {
      widgets.add(_buildAssignSection(context, ticket, prov));
      widgets.add(const SizedBox(height: 10));
    }

    if (isTech && isOwned) {
      if (ticket.status == 'assigned') {
        actions.add((
          'Mark In Progress',
          'in_progress',
          AppColors.statusInProgress,
        ));
      }
      if (ticket.status == 'in_progress') {
        actions.add(('Mark Completed', 'completed', AppColors.statusCompleted));
      }
    }

    if (user.isOperationsManager || user.isAdmin) {
      if (ticket.status == 'pending_approval' || ticket.status == 'logged') {
        actions.add(('Approve (Logged)', 'logged', AppColors.statusLogged));
      }
      if (ticket.status == 'completed') {
        actions.add(('Close Ticket', 'closed', AppColors.statusClosed));
      }
    }

    if (actions.isEmpty && widgets.isEmpty) return const SizedBox.shrink();

    return Column(
      children: [
        ...widgets,
        ...actions.map((a) {
          return Padding(
            padding: const EdgeInsets.only(bottom: 10),
            child: SizedBox(
              width: double.infinity,
              height: 48,
              child: ElevatedButton(
                onPressed: prov.loading
                    ? null
                    : () => _doStatusChange(context, ticket.id, a.$2, a.$1),
                style: ElevatedButton.styleFrom(
                  backgroundColor: a.$3,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                  elevation: 0,
                ),
                child: Text(
                  a.$1,
                  style: const TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
            ),
          );
        }),
      ],
    );
  }

  bool _canManagePhases(UserModel user, TicketModel ticket) {
    if (user.isAdmin || user.isOperationsManager) {
      return true;
    }

    return user.isTechnician && ticket.technician?.id == user.id;
  }

  Widget _buildWorkProgressSection(
    BuildContext context,
    TicketModel ticket,
    UserModel user,
    TicketProvider prov,
  ) {
    final canManage = _canManagePhases(user, ticket);

    return _InfoCard(
      children: [
        Row(
          children: [
            const Expanded(
              child: Text(
                'Technician Work Progress & Phases',
                style: TextStyle(
                  color: AppColors.textPrimary,
                  fontSize: 14,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ),
            if (canManage)
              TextButton.icon(
                onPressed: _savingPhase
                    ? null
                  : () => _showAddPhaseDialog(ticket.id),
                icon: const Icon(Icons.add, size: 16),
                label: const Text('Add Phase'),
                style: TextButton.styleFrom(
                  foregroundColor: AppColors.primary,
                  padding: const EdgeInsets.symmetric(
                    horizontal: 10,
                    vertical: 6,
                  ),
                ),
              ),
          ],
        ),
        const SizedBox(height: 8),
        _DetailRow(Icons.flag_outlined, 'Current Status', ticket.status),
        if (ticket.technician != null) ...[
          const Divider(color: AppColors.divider, height: 16),
          _DetailRow(
            Icons.engineering_outlined,
            'Assigned Technician',
            ticket.technician!.name,
          ),
        ],
        const SizedBox(height: 10),
        if (_loadingPhases)
          const Center(
            child: Padding(
              padding: EdgeInsets.symmetric(vertical: 10),
              child: CircularProgressIndicator(color: AppColors.primary),
            ),
          )
        else if (_phases.isEmpty)
          const Text(
            'No work phases available yet.',
            style: TextStyle(color: AppColors.textSecondary, fontSize: 13),
          )
        else
          ..._phases.map(
            (phase) => Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: _buildPhaseItem(
                context,
                ticket.id,
                phase,
                user,
                canManage,
                prov,
              ),
            ),
          ),
      ],
    );
  }

  Widget _buildPhaseItem(
    BuildContext context,
    int ticketId,
    Map<String, dynamic> phase,
    UserModel user,
    bool canManage,
    TicketProvider prov,
  ) {
    final phaseId = phase['id'] as int?;
    final status = (phase['status'] as String?) ?? 'unknown';
    final name = (phase['phase_name'] as String?) ?? 'Unnamed Phase';
    final notes = (phase['technician_notes'] as String?) ?? '';
    final managerNotes = (phase['manager_notes'] as String?) ?? '';
    final startedAt = phase['started_at'] as String?;
    final completedAt = phase['completed_at'] as String?;
    final attachments = ((phase['attachments'] as List<dynamic>? ?? const [])
            .whereType<Map<String, dynamic>>())
        .toList(growable: false);
    final isCompleted = status == 'completed';
    final canEditManagerComment = user.isOperationsManager || user.isAdmin;
    final canEditTechnicianResponse = canManage && !canEditManagerComment;
    final canUploadForPhase = canManage && !isCompleted && phaseId != null;

    return Container(
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: AppColors.background,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: AppColors.divider),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  name,
                  style: const TextStyle(
                    color: AppColors.textPrimary,
                    fontSize: 13,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
              StatusBadge(status: status),
            ],
          ),
          if (notes.trim().isNotEmpty) ...[
            const SizedBox(height: 6),
            const Text(
              'Technician Notes',
              style: TextStyle(
                color: AppColors.textSecondary,
                fontSize: 11,
                fontWeight: FontWeight.w700,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              notes,
              style: const TextStyle(
                color: AppColors.textSecondary,
                fontSize: 12,
                height: 1.4,
              ),
            ),
          ],
          if (managerNotes.trim().isNotEmpty) ...[
            const SizedBox(height: 8),
            const Text(
              'Manager Comment',
              style: TextStyle(
                color: AppColors.textSecondary,
                fontSize: 11,
                fontWeight: FontWeight.w700,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              managerNotes,
              style: const TextStyle(
                color: AppColors.textPrimary,
                fontSize: 12,
                height: 1.4,
              ),
            ),
          ],
          const SizedBox(height: 8),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              _buildPhaseActionChip(
                icon: Icons.edit_note_outlined,
                label: canEditManagerComment ? 'Add Comment' : 'Respond',
                onPressed: phaseId == null || _savingPhase
                    ? null
                    : () => _showUpdatePhaseDialog(
                          ticketId,
                        phaseId,
                          currentTechnicianNotes: notes,
                          currentManagerNotes: managerNotes,
                          allowTechnicianNotes: canEditTechnicianResponse,
                          allowManagerNotes: canEditManagerComment,
                        ),
                ),
              _buildPhaseActionChip(
                icon: Icons.photo_camera_outlined,
                label: 'Take Photo',
                onPressed: canUploadForPhase && !_savingPhase
                  ? () => _uploadPhaseFromCamera(ticketId, phaseId)
                    : null,
              ),
              _buildPhaseActionChip(
                icon: Icons.image_outlined,
                label: 'Upload Image',
                onPressed: canUploadForPhase && !_savingPhase
                  ? () => _uploadPhaseFromGallery(ticketId, phaseId)
                    : null,
              ),
              _buildPhaseActionChip(
                icon: Icons.attach_file_outlined,
                label: 'Upload Document',
                onPressed: canUploadForPhase && !_savingPhase
                  ? () => _uploadPhaseDocument(ticketId, phaseId)
                    : null,
              ),
              _buildPhaseActionChip(
                icon: Icons.check_circle_outline,
                label: 'Mark Completed',
                onPressed: canUploadForPhase && !_savingPhase
                  ? () => _completePhase(ticketId, phaseId, prov)
                    : null,
              ),
            ],
          ),
          if (attachments.isNotEmpty) ...[
            const SizedBox(height: 10),
            const Text(
              'Attachments',
              style: TextStyle(
                color: AppColors.textSecondary,
                fontSize: 11,
                fontWeight: FontWeight.w700,
              ),
            ),
            const SizedBox(height: 6),
            ...attachments.map(
              (attachment) {
                final fileName = (attachment['file_name'] as String?) ?? 'File';
                final attachmentType =
                    (attachment['attachment_type'] as String?) ?? 'document';
                final createdAt = attachment['created_at'] as String?;
                return Padding(
                  padding: const EdgeInsets.only(bottom: 6),
                  child: Row(
                    children: [
                      Icon(
                        attachmentType == 'image'
                            ? Icons.image_outlined
                            : Icons.description_outlined,
                        color: AppColors.primary,
                        size: 16,
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          fileName,
                          style: const TextStyle(
                            color: AppColors.textPrimary,
                            fontSize: 12,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                      const SizedBox(width: 8),
                      Text(
                        _formatDateTime(createdAt),
                        style: const TextStyle(
                          color: AppColors.textSecondary,
                          fontSize: 10,
                        ),
                      ),
                    ],
                  ),
                );
              },
            ),
          ],
          const SizedBox(height: 8),
          Text(
            'Started: ${_formatDateTime(startedAt)}',
            style: const TextStyle(
              color: AppColors.textSecondary,
              fontSize: 11,
            ),
          ),
          Text(
            'Completed: ${_formatDateTime(completedAt)}',
            style: const TextStyle(
              color: AppColors.textSecondary,
              fontSize: 11,
            ),
          ),
          if (_savingPhase)
            const Padding(
              padding: EdgeInsets.only(top: 8),
              child: SizedBox(
                width: 18,
                height: 18,
                child: CircularProgressIndicator(
                  strokeWidth: 2,
                  color: AppColors.primary,
                ),
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildPhaseActionChip({
    required IconData icon,
    required String label,
    required VoidCallback? onPressed,
  }) {
    return OutlinedButton.icon(
      onPressed: onPressed,
      icon: Icon(icon, size: 16),
      label: Text(label),
      style: OutlinedButton.styleFrom(
        side: BorderSide(
          color: onPressed == null ? AppColors.divider : AppColors.primary,
        ),
        foregroundColor:
            onPressed == null ? AppColors.textSecondary : AppColors.primary,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(10),
        ),
      ),
    );
  }

  Future<void> _showUpdatePhaseDialog(
    int ticketId,
    int phaseId, {
    required String currentTechnicianNotes,
    required String currentManagerNotes,
    required bool allowTechnicianNotes,
    required bool allowManagerNotes,
  }) async {
    String technicianNotes = currentTechnicianNotes;
    String managerNotes = currentManagerNotes;

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: const Text('Update Phase'),
        content: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              if (allowTechnicianNotes)
                TextFormField(
                  initialValue: technicianNotes,
                  onChanged: (value) => technicianNotes = value,
                  decoration: const InputDecoration(
                    labelText: 'Technician Notes',
                  ),
                  minLines: 3,
                  maxLines: 6,
                ),
              if (allowTechnicianNotes && allowManagerNotes)
                const SizedBox(height: 10),
              if (allowManagerNotes)
                TextFormField(
                  initialValue: managerNotes,
                  onChanged: (value) => managerNotes = value,
                  decoration: const InputDecoration(labelText: 'Manager Comment'),
                  minLines: 3,
                  maxLines: 6,
                ),
            ],
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, false),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(dialogContext, true),
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.primary),
            child: const Text('Save', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );

    if (confirmed != true || !mounted) {
      return;
    }

    final payload = <String, dynamic>{};
    if (allowTechnicianNotes) {
      payload['technician_notes'] = technicianNotes.trim().isEmpty
          ? null
          : technicianNotes.trim();
    }
    if (allowManagerNotes) {
      payload['manager_notes'] = managerNotes.trim().isEmpty
          ? null
          : managerNotes.trim();
    }

    await _updatePhase(ticketId, phaseId, payload);
  }

  Future<void> _updatePhase(
    int ticketId,
    int phaseId,
    Map<String, dynamic> payload,
  ) async {
    setState(() => _savingPhase = true);

    try {
      await ApiService.instance.updateTicketPhase(ticketId, phaseId, payload);
      await _refreshTicketData();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Phase updated successfully.')),
      );
    } on DioException catch (e) {
      final code = e.response?.statusCode;
      final isEndpointMissing = code == 404 || code == 405;
      final technicianNotes = payload['technician_notes'] as String?;

      if (isEndpointMissing &&
          technicianNotes != null &&
          technicianNotes.trim().isNotEmpty) {
        try {
          await _fallbackCreateTechnicianResponsePhase(ticketId, technicianNotes);
          await _refreshTicketData();
          if (!mounted) return;
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Phase response saved successfully.')),
          );
          return;
        } catch (_) {
          // Fall through to generic failure message below.
        }
      }

      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Failed to update phase.')),
      );
    } catch (_) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Failed to update phase.')),
      );
    } finally {
      if (mounted) {
        setState(() => _savingPhase = false);
      }
    }
  }

  Future<void> _fallbackCreateTechnicianResponsePhase(
    int ticketId,
    String technicianNotes,
  ) async {
    final timestamp = DateFormat('yyyy-MM-dd HH:mm').format(DateTime.now());

    await ApiService.instance.addTicketPhase(ticketId, {
      'phase_name': 'Response $timestamp',
      'technician_notes': technicianNotes.trim(),
    });
  }

  Future<void> _uploadPhaseFromCamera(int ticketId, int phaseId) async {
    final picked = await _imagePicker.pickImage(
      source: ImageSource.camera,
      imageQuality: 85,
    );
    if (picked == null) {
      return;
    }

    await _uploadPhaseAttachment(
      ticketId: ticketId,
      phaseId: phaseId,
      filePath: picked.path,
      attachmentType: 'image',
    );
  }

  Future<void> _uploadPhaseFromGallery(int ticketId, int phaseId) async {
    final picked = await _imagePicker.pickImage(
      source: ImageSource.gallery,
      imageQuality: 90,
    );
    if (picked == null) {
      return;
    }

    await _uploadPhaseAttachment(
      ticketId: ticketId,
      phaseId: phaseId,
      filePath: picked.path,
      attachmentType: 'image',
    );
  }

  Future<void> _uploadPhaseDocument(int ticketId, int phaseId) async {
    final picked = await FilePicker.platform.pickFiles(
      type: FileType.custom,
      allowedExtensions: const ['pdf', 'doc', 'docx'],
      allowMultiple: false,
      withData: false,
    );

    final filePath = picked?.files.single.path;
    if (filePath == null || filePath.isEmpty) {
      return;
    }

    await _uploadPhaseAttachment(
      ticketId: ticketId,
      phaseId: phaseId,
      filePath: filePath,
      attachmentType: 'document',
    );
  }

  Future<void> _uploadPhaseAttachment({
    required int ticketId,
    required int phaseId,
    required String filePath,
    required String attachmentType,
  }) async {
    setState(() => _savingPhase = true);

    try {
      await ApiService.instance.uploadPhaseAttachment(
        ticketId: ticketId,
        phaseId: phaseId,
        filePath: filePath,
        attachmentType: attachmentType,
      );
      await _refreshTicketData();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Phase attachment uploaded.')),
      );
    } catch (_) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Failed to upload attachment.')),
      );
    } finally {
      if (mounted) {
        setState(() => _savingPhase = false);
      }
    }
  }

  Future<void> _showAddPhaseDialog(int ticketId) async {
    String phaseName = '';
    String description = '';
    String technicianNotes = '';

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: const Text('Add Work Phase'),
        content: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              TextField(
                onChanged: (value) => phaseName = value,
                decoration: const InputDecoration(labelText: 'Phase Name'),
              ),
              const SizedBox(height: 10),
              TextField(
                onChanged: (value) => description = value,
                decoration: const InputDecoration(labelText: 'Description'),
                minLines: 2,
                maxLines: 4,
              ),
              const SizedBox(height: 10),
              TextField(
                onChanged: (value) => technicianNotes = value,
                decoration: const InputDecoration(labelText: 'Technician Notes'),
                minLines: 2,
                maxLines: 4,
              ),
            ],
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, false),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () {
              if (phaseName.trim().isEmpty) {
                return;
              }
              Navigator.pop(dialogContext, true);
            },
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.primary),
            child: const Text('Save', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );

    if (confirmed != true || !mounted) {
      return;
    }

    final payload = {
      'phase_name': phaseName.trim(),
      'description': description.trim().isEmpty ? null : description.trim(),
      'technician_notes': technicianNotes.trim().isEmpty
          ? null
          : technicianNotes.trim(),
    };

    await _addPhase(ticketId, payload);
  }

  Future<void> _addPhase(
    int ticketId,
    Map<String, dynamic> payload,
  ) async {
    final ticketProvider = context.read<TicketProvider>();
    setState(() => _savingPhase = true);

    try {
      await ApiService.instance.addTicketPhase(ticketId, payload);
      await _loadPhases();
      await ticketProvider.loadTicket(ticketId);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Work phase added successfully.')),
      );
    } catch (_) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Failed to add work phase.')),
      );
    } finally {
      if (mounted) {
        setState(() => _savingPhase = false);
      }
    }
  }

  Future<void> _completePhase(
    int ticketId,
    int phaseId,
    TicketProvider prov,
  ) async {
    setState(() => _savingPhase = true);

    try {
      await ApiService.instance.completeTicketPhase(ticketId, phaseId);
      await _loadPhases();
      await prov.loadTicket(ticketId);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Phase marked as completed.')),
      );
    } catch (_) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Failed to update phase.')),
      );
    } finally {
      if (mounted) {
        setState(() => _savingPhase = false);
      }
    }
  }

  Widget _buildAssignSection(
    BuildContext context,
    TicketModel ticket,
    TicketProvider prov,
  ) {
    final currentTechId = ticket.technician?.id;
    final value = _selectedTechnicianId ?? currentTechId;
    final validValue = _technicians.any((t) => t['id'] == value) ? value : null;

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: const [BoxShadow(color: AppColors.shadow, blurRadius: 6)],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Assign Technician',
            style: TextStyle(
              color: AppColors.textPrimary,
              fontWeight: FontWeight.w700,
              fontSize: 14,
            ),
          ),
          const SizedBox(height: 8),
          DropdownButtonFormField<int>(
            initialValue: validValue,
            items: _technicians
                .map(
                  (t) => DropdownMenuItem<int>(
                    value: t['id'] as int,
                    child: Text(t['name'] as String? ?? 'Unknown'),
                  ),
                )
                .toList(),
            onChanged: _assigning
                ? null
                : (v) => setState(() => _selectedTechnicianId = v),
            decoration: InputDecoration(
              hintText: 'Select technician',
              isDense: true,
              filled: true,
              fillColor: AppColors.background,
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(10),
                borderSide: const BorderSide(color: AppColors.divider),
              ),
              enabledBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(10),
                borderSide: const BorderSide(color: AppColors.divider),
              ),
            ),
          ),
          const SizedBox(height: 10),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: _assigning || validValue == null
                  ? null
                  : () => _doAssign(context, ticket.id, validValue, prov),
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.primary,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(10),
                ),
              ),
              child: _assigning
                  ? const SizedBox(
                      width: 18,
                      height: 18,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        color: Colors.white,
                      ),
                    )
                  : const Text('Assign', style: TextStyle(color: Colors.white)),
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _doAssign(
    BuildContext context,
    int ticketId,
    int technicianId,
    TicketProvider prov,
  ) async {
    final messenger = ScaffoldMessenger.of(context);
    setState(() => _assigning = true);
    final success = await prov.assignTicket(ticketId, technicianId);
    if (!mounted) return;
    setState(() => _assigning = false);

    final message = success
        ? 'Technician assigned successfully.'
        : (prov.error ?? 'Failed to assign technician.');

    messenger.showSnackBar(SnackBar(content: Text(message)));
    if (success) {
      await prov.loadTicket(ticketId);
    }
  }

  Future<void> _doStatusChange(
    BuildContext ctx,
    int id,
    String status,
    String label,
  ) async {
    final confirmed = await showDialog<bool>(
      context: ctx,
      builder: (dialogContext) => AlertDialog(
        title: const Text('Confirm'),
        content: Text('Set this task to "$label"?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, false),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(dialogContext, true),
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.primary),
            child: const Text('Confirm', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );
    if (confirmed != true || !mounted) return;
    await context.read<TicketProvider>().changeStatus(id, status);
    if (mounted) context.read<TicketProvider>().loadTicket(id);
  }
}

class _InfoCard extends StatelessWidget {
  final List<Widget> children;
  const _InfoCard({required this.children});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        boxShadow: const [BoxShadow(color: AppColors.shadow, blurRadius: 6)],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: children,
      ),
    );
  }
}

class _DetailRow extends StatelessWidget {
  final IconData icon;
  final String label;
  final String value;
  const _DetailRow(this.icon, this.label, this.value);

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Icon(icon, color: AppColors.primary, size: 18),
        const SizedBox(width: 10),
        Text(
          '$label: ',
          style: const TextStyle(color: AppColors.textSecondary, fontSize: 13),
        ),
        Expanded(
          child: Text(
            value,
            style: const TextStyle(
              color: AppColors.textPrimary,
              fontSize: 13,
              fontWeight: FontWeight.w500,
            ),
            overflow: TextOverflow.ellipsis,
          ),
        ),
      ],
    );
  }
}
