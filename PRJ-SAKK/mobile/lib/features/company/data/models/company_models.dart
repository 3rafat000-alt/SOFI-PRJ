// Models for the "join as a company" (انضم كشركة) registration feature —
// the third business audience after agents/merchants. Mirrors partner_models.

/// A KYC document type option (key + Arabic label) for a company application.
class CompanyDocType {
  final String key;
  final String label;

  const CompanyDocType({required this.key, required this.label});

  factory CompanyDocType.fromJson(Map<String, dynamic> json) => CompanyDocType(
        key: json['key']?.toString() ?? '',
        label: json['label']?.toString() ?? '',
      );
}

/// An uploaded KYC document attached to the company application.
class CompanyDocument {
  final int id;
  final String documentType;
  final String typeLabel;
  final String status; // pending | approved | rejected
  final String? statusColor;
  final String? rejectionReason;

  const CompanyDocument({
    required this.id,
    required this.documentType,
    required this.typeLabel,
    required this.status,
    this.statusColor,
    this.rejectionReason,
  });

  factory CompanyDocument.fromJson(Map<String, dynamic> json) => CompanyDocument(
        id: json['id'] as int,
        documentType: json['document_type']?.toString() ?? '',
        typeLabel: json['type_label']?.toString() ?? '',
        status: json['status']?.toString() ?? 'pending',
        statusColor: json['status_color']?.toString(),
        rejectionReason: json['rejection_reason']?.toString(),
      );

  bool get isApproved => status == 'approved';
  bool get isRejected => status == 'rejected';
}

/// The current user's company application with KYC status + documents.
class CompanyApplication {
  final int id;
  final String name;
  final String? legalName;
  final String code;
  final bool isActive;
  final bool isVerified;
  final bool payrollEnabled;
  final String kycStatus;
  final String kycStatusLabel;
  final String? kycStatusColor;
  final String? rejectionReason;
  final List<CompanyDocument> documents;

  const CompanyApplication({
    required this.id,
    required this.name,
    this.legalName,
    required this.code,
    required this.isActive,
    required this.isVerified,
    required this.payrollEnabled,
    required this.kycStatus,
    required this.kycStatusLabel,
    this.kycStatusColor,
    this.rejectionReason,
    required this.documents,
  });

  factory CompanyApplication.fromJson(Map<String, dynamic> json) =>
      CompanyApplication(
        id: json['id'] as int,
        name: json['name']?.toString() ?? '',
        legalName: json['legal_name']?.toString(),
        code: json['company_code']?.toString() ?? '',
        isActive: json['is_active'] == true,
        isVerified: json['is_verified'] == true,
        payrollEnabled: json['payroll_enabled'] == true,
        kycStatus: json['kyc_status']?.toString() ?? 'pending',
        kycStatusLabel: json['kyc_status_label']?.toString() ?? '',
        kycStatusColor: json['kyc_status_color']?.toString(),
        rejectionReason: json['kyc_rejection_reason']?.toString(),
        documents: (json['documents'] as List?)
                ?.map((e) => CompanyDocument.fromJson(e as Map<String, dynamic>))
                .toList() ??
            const [],
      );

  bool get isApproved => kycStatus == 'approved';
}

/// The full company state for the current user (application + doc types).
class CompanyState {
  final CompanyApplication? company;
  final List<CompanyDocType> docTypes;

  const CompanyState({this.company, required this.docTypes});

  factory CompanyState.fromJson(Map<String, dynamic> json) => CompanyState(
        company: json['company'] != null
            ? CompanyApplication.fromJson(json['company'] as Map<String, dynamic>)
            : null,
        docTypes: (json['document_types'] as List?)
                ?.map((e) => CompanyDocType.fromJson(e as Map<String, dynamic>))
                .toList() ??
            const [],
      );
}
