// Models for the "join as agent / merchant" partner-application feature.

/// A document type option (key + Arabic label) for an application.
class PartnerDocType {
  final String key;
  final String label;

  const PartnerDocType({required this.key, required this.label});

  factory PartnerDocType.fromJson(Map<String, dynamic> json) => PartnerDocType(
        key: json['key']?.toString() ?? '',
        label: json['label']?.toString() ?? '',
      );
}

/// An uploaded document attached to an application.
class PartnerDocument {
  final int id;
  final String documentType;
  final String typeLabel;
  final String status; // pending | approved | rejected
  final String? statusColor;
  final String? rejectionReason;

  const PartnerDocument({
    required this.id,
    required this.documentType,
    required this.typeLabel,
    required this.status,
    this.statusColor,
    this.rejectionReason,
  });

  factory PartnerDocument.fromJson(Map<String, dynamic> json) => PartnerDocument(
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

/// An agent or merchant application with its KYC status + documents.
class PartnerApplication {
  final int id;
  final String type; // agent | merchant
  final String name; // agent name or store name
  final String code;
  final bool isActive;
  final String kycStatus;
  final String kycStatusLabel;
  final String? kycStatusColor;
  final String? rejectionReason;
  final List<PartnerDocument> documents;

  const PartnerApplication({
    required this.id,
    required this.type,
    required this.name,
    required this.code,
    required this.isActive,
    required this.kycStatus,
    required this.kycStatusLabel,
    this.kycStatusColor,
    this.rejectionReason,
    required this.documents,
  });

  factory PartnerApplication.fromJson(Map<String, dynamic> json) {
    final isAgent = json['type'] == 'agent';
    return PartnerApplication(
      id: json['id'] as int,
      type: json['type']?.toString() ?? 'agent',
      name: (isAgent ? json['name'] : json['store_name'])?.toString() ?? '',
      code: (isAgent ? json['agent_code'] : json['merchant_code'])?.toString() ?? '',
      isActive: json['is_active'] == true,
      kycStatus: json['kyc_status']?.toString() ?? 'pending',
      kycStatusLabel: json['kyc_status_label']?.toString() ?? '',
      kycStatusColor: json['kyc_status_color']?.toString(),
      rejectionReason: json['kyc_rejection_reason']?.toString(),
      documents: (json['documents'] as List?)
              ?.map((e) => PartnerDocument.fromJson(e as Map<String, dynamic>))
              .toList() ??
          const [],
    );
  }

  bool get isApproved => kycStatus == 'approved';
  bool get isRejected => kycStatus == 'rejected';
  bool get needsDocuments => kycStatus == 'documents_required';
}

/// The full partner state for the current user (agent + merchant + doc types).
class PartnerState {
  final PartnerApplication? agent;
  final PartnerApplication? merchant;
  final List<PartnerDocType> agentDocTypes;
  final List<PartnerDocType> merchantDocTypes;

  const PartnerState({
    this.agent,
    this.merchant,
    required this.agentDocTypes,
    required this.merchantDocTypes,
  });

  factory PartnerState.fromJson(Map<String, dynamic> json) {
    final docTypes = json['document_types'] as Map<String, dynamic>? ?? {};
    return PartnerState(
      agent: json['agent'] != null
          ? PartnerApplication.fromJson(json['agent'] as Map<String, dynamic>)
          : null,
      merchant: json['merchant'] != null
          ? PartnerApplication.fromJson(json['merchant'] as Map<String, dynamic>)
          : null,
      agentDocTypes: (docTypes['agent'] as List?)
              ?.map((e) => PartnerDocType.fromJson(e as Map<String, dynamic>))
              .toList() ??
          const [],
      merchantDocTypes: (docTypes['merchant'] as List?)
              ?.map((e) => PartnerDocType.fromJson(e as Map<String, dynamic>))
              .toList() ??
          const [],
    );
  }
}
