namespace Yotee;

/** PRD §58 report reasons: スパム / 誤情報 / なりすまし / 不適切な内容 / その他. */
enum ReportReason {
  spam: "spam",
  misinformation: "misinformation",
  impersonation: "impersonation",
  inappropriate: "inappropriate",
  other: "other",
}

model CreateReportRequest {
  reason: ReportReason;

  /** Free-text detail, required when `reason` is `other`. */
  detail?: string;
}
