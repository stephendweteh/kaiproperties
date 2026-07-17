class TicketModel {
  final int id;
  final String ticketNo;
  final String title;
  final String description;
  final String? unit;
  final String status;
  final String priority;
  final String? etd;
  final String? estimatedCost;
  final String? estimatedCostCurrency;
  final String? startedAt;
  final String? completedAt;
  final String? closedAt;
  final bool requiresAdditionalCost;
  final PropertyRef? property;
  final CategoryRef? category;
  final UserRef? reporter;
  final UserRef? technician;
  final List<TicketAttachmentModel> attachments;
  final String? createdAt;
  final String? updatedAt;

  const TicketModel({
    required this.id,
    required this.ticketNo,
    required this.title,
    required this.description,
    this.unit,
    required this.status,
    required this.priority,
    this.etd,
    this.estimatedCost,
    this.estimatedCostCurrency,
    this.startedAt,
    this.completedAt,
    this.closedAt,
    required this.requiresAdditionalCost,
    this.property,
    this.category,
    this.reporter,
    this.technician,
    this.attachments = const [],
    this.createdAt,
    this.updatedAt,
  });

  factory TicketModel.fromJson(Map<String, dynamic> json) => TicketModel(
        id: json['id'] as int,
        ticketNo: json['ticket_no'] as String,
        title: json['title'] as String,
        description: json['description'] as String,
        unit: json['unit'] as String?,
        status: json['status'] as String,
        priority: json['priority'] as String,
        etd: json['etd'] as String?,
        estimatedCost: json['estimated_cost'] as String?,
        estimatedCostCurrency: json['estimated_cost_currency'] as String?,
        startedAt: json['started_at'] as String?,
        completedAt: json['completed_at'] as String?,
        closedAt: json['closed_at'] as String?,
        requiresAdditionalCost:
            (json['requires_additional_cost'] as bool?) ?? false,
        property: json['property'] != null
            ? PropertyRef.fromJson(json['property'] as Map<String, dynamic>)
            : null,
        category: json['category'] != null
            ? CategoryRef.fromJson(json['category'] as Map<String, dynamic>)
            : null,
        reporter: json['reporter'] != null
            ? UserRef.fromJson(json['reporter'] as Map<String, dynamic>)
            : null,
        technician: json['technician'] != null
            ? UserRef.fromJson(json['technician'] as Map<String, dynamic>)
            : null,
        attachments: (json['attachments'] as List<dynamic>? ?? const [])
          .whereType<Map<String, dynamic>>()
          .map(TicketAttachmentModel.fromJson)
          .toList(growable: false),
        createdAt: json['created_at'] as String?,
        updatedAt: json['updated_at'] as String?,
      );

  bool get isOverdue {
    if (etd == null) return false;
    final etdDate = DateTime.tryParse(etd!);
    if (etdDate == null) return false;
    return etdDate.isBefore(DateTime.now()) &&
        !['completed', 'closed', 'rejected'].contains(status);
  }
}

class PropertyRef {
  final int id;
  final String name;
  final String? code;

  const PropertyRef({required this.id, required this.name, this.code});

  factory PropertyRef.fromJson(Map<String, dynamic> json) => PropertyRef(
        id: json['id'] as int,
        name: json['name'] as String,
        code: json['code'] as String?,
      );
}

class CategoryRef {
  final int id;
  final String name;

  const CategoryRef({required this.id, required this.name});

  factory CategoryRef.fromJson(Map<String, dynamic> json) => CategoryRef(
        id: json['id'] as int,
        name: json['name'] as String,
      );
}

class UserRef {
  final int id;
  final String name;
  final String? email;

  const UserRef({required this.id, required this.name, this.email});

  factory UserRef.fromJson(Map<String, dynamic> json) => UserRef(
        id: json['id'] as int,
        name: json['name'] as String,
        email: json['email'] as String?,
      );
}

class TicketAttachmentModel {
  final int id;
  final String fileName;
  final String mimeType;
  final int? fileSize;
  final String attachmentType;
  final String? url;
  final String? createdAt;

  const TicketAttachmentModel({
    required this.id,
    required this.fileName,
    required this.mimeType,
    this.fileSize,
    required this.attachmentType,
    this.url,
    this.createdAt,
  });

  factory TicketAttachmentModel.fromJson(Map<String, dynamic> json) {
    int? asInt(dynamic value) {
      if (value is int) return value;
      if (value is String) return int.tryParse(value);
      return null;
    }

    return TicketAttachmentModel(
      id: asInt(json['id']) ?? 0,
      fileName: (json['file_name'] as String?) ?? 'File',
      mimeType: (json['mime_type'] as String?) ?? 'application/octet-stream',
      fileSize: asInt(json['file_size']),
      attachmentType: (json['attachment_type'] as String?) ?? 'document',
      url: json['url'] as String?,
      createdAt: json['created_at'] as String?,
    );
  }
}
