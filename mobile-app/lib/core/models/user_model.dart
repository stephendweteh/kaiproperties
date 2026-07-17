import '../constants/app_constants.dart';

class UserModel {
  final int id;
  final String name;
  final String email;
  final String role;
  final String? phone;
  final String? profilePhotoUrl;

  const UserModel({
    required this.id,
    required this.name,
    required this.email,
    required this.role,
    this.phone,
    this.profilePhotoUrl,
  });

  factory UserModel.fromJson(Map<String, dynamic> json) => UserModel(
    id: json['id'] as int,
    name: json['name'] as String,
    email: json['email'] as String,
    role: json['role'] as String,
    phone: json['phone'] as String?,
    profilePhotoUrl: normalizeProfilePhotoUrl(json['profile_photo_url']),
  );

  Map<String, dynamic> toJson() => {
    'id': id,
    'name': name,
    'email': email,
    'role': role,
    'phone': phone,
    'profile_photo_url': profilePhotoUrl,
  };

  UserModel copyWith({
    int? id,
    String? name,
    String? email,
    String? role,
    String? phone,
    String? profilePhotoUrl,
  }) {
    return UserModel(
      id: id ?? this.id,
      name: name ?? this.name,
      email: email ?? this.email,
      role: role ?? this.role,
      phone: phone ?? this.phone,
      profilePhotoUrl: profilePhotoUrl == null
          ? this.profilePhotoUrl
          : normalizeProfilePhotoUrl(profilePhotoUrl),
    );
  }

  static String? normalizeProfilePhotoUrl(dynamic rawUrl) {
    final raw = (rawUrl as String?)?.trim();
    if (raw == null || raw.isEmpty) {
      return null;
    }

    final baseUri = Uri.parse(kBaseUrl);
    final origin = '${baseUri.scheme}://${baseUri.authority}';

    if (raw.startsWith('//')) {
      return '${baseUri.scheme}:$raw';
    }

    final parsed = Uri.tryParse(raw);
    if (parsed != null && parsed.hasScheme) {
      if (parsed.host == 'localhost' ||
          parsed.host == '127.0.0.1' ||
          parsed.host == '0.0.0.0') {
        return Uri(
          scheme: baseUri.scheme,
          host: baseUri.host,
          port: baseUri.hasPort ? baseUri.port : null,
          path: parsed.path,
          query: parsed.hasQuery ? parsed.query : null,
          fragment: parsed.hasFragment ? parsed.fragment : null,
        ).toString();
      }

      if (parsed.host == baseUri.host && parsed.scheme != baseUri.scheme) {
        return Uri(
          scheme: baseUri.scheme,
          host: parsed.host,
          port: parsed.hasPort ? parsed.port : null,
          path: parsed.path,
          query: parsed.hasQuery ? parsed.query : null,
          fragment: parsed.hasFragment ? parsed.fragment : null,
        ).toString();
      }

      return parsed.toString();
    }

    if (raw.startsWith('/')) {
      return '$origin$raw';
    }

    return '$origin/$raw';
  }

  bool get isAdmin => role == 'admin';
  bool get isOperationsManager => role == 'operations_manager';
  bool get isManagingDirector => role == 'managing_director';
  bool get isGeneralManager => role == 'general_manager';
  bool get isTechnician => role == 'technician';
  bool get isTenant => role == 'tenant';
  bool get isManagement =>
      isAdmin || isOperationsManager || isManagingDirector || isGeneralManager;

  bool get canCreateTickets =>
      isTenant ||
      isAdmin ||
      isOperationsManager ||
      isManagingDirector ||
      isGeneralManager;

  bool canEditTicket({required int? reportedById, required String status}) {
    if (isAdmin || isOperationsManager) {
      return true;
    }

    if ((isTenant || isManagingDirector || isGeneralManager) &&
        reportedById == id &&
        (status == 'pending_approval' ||
            status == 'logged' ||
            status == 'rejected')) {
      return true;
    }

    return false;
  }

  String get displayRole {
    switch (role) {
      case 'admin':
        return 'Admin';
      case 'operations_manager':
        return 'Operations Manager';
      case 'managing_director':
        return 'Managing Director';
      case 'general_manager':
        return 'General Manager';
      case 'technician':
        return 'Technician';
      case 'tenant':
        return 'Tenant';
      default:
        return role;
    }
  }
}
