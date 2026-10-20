import { ApiError, formatNumber, t } from "./api";

export function Icon({ name, size = 19 }: { name: string; size?: number }) {
  const paths: Record<string, string> = {
    overview: "M3 3h7v7H3z M14 3h7v7h-7z M3 14h7v7H3z M14 14h7v7h-7z",
    inventory: "M3 7l9-5 9 5-9 5z M3 12l9 5 9-5 M3 17l9 5 9-5",
    studio: "M3 3h18v18H3z M3 8h18 M15 8v13 M3 17h12",
    campaigns: "M4 10v5h4l10 4V6L8 10z M8 15l2 6h3l-2-5",
    creatives: "M3 3h18v18H3z M3 16l5-5 5 4 4-5 4 6 M8 7h.01",
    reports: "M3 21h18 M6 17v-5 M12 17V7 M18 17V3",
    settings:
      "M12 8a4 4 0 1 0 0 8 4 4 0 1 0 0-8 M12 2v3 M12 19v3 M2 12h3 M19 12h3 M5 5l2 2 M17 17l2 2 M5 19l2-2 M17 7l2-2",
    marketplace: "M3 9h18l-2-6H5z M4 9v12h16V9 M9 21v-7h6v7",
    organization: "M3 21V8l9-5 9 5v13 M8 21v-5h8v5 M8 10h.01 M16 10h.01",
    plus: "M12 5v14 M5 12h14",
    arrow: "M5 12h14 M14 7l5 5-5 5",
    check: "M5 12l4 4L19 6",
    search: "M11 3a8 8 0 1 0 0 16 8 8 0 1 0 0-16 M17 17l4 4",
    close: "M6 6l12 12 M6 18L18 6",
    clock: "M12 3a9 9 0 1 0 0 18 9 9 0 1 0 0-18 M12 7v5l3 2",
    shield: "M12 2l9 4v6c0 5-9 10-9 10S3 17 3 12V6z M8 12l3 3 5-6",
  };
  return (
    <svg
      width={size}
      height={size}
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="1.7"
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden="true"
    >
      <path d={paths[name] ?? paths.overview} />
    </svg>
  );
}
export function Button({ children, kind = "secondary", icon, ...props }: any) {
  return (
    <button
      type="button"
      className={`adp-button adp-button-${kind}`}
      {...props}
    >
      {icon && <Icon name={icon} size={16} />}
      <span>{children}</span>
    </button>
  );
}
export function Status({ value }: { value?: string }) {
  const positive = [
    "active",
    "published",
    "approved",
    "ready",
    "healthy",
    "passed",
    "available",
  ].includes(value ?? "");
  const warning = [
    "draft",
    "pending",
    "pending_review",
    "paused",
    "reserved",
    "needs_changes",
  ].includes(value ?? "");
  return (
    <span
      className={`adp-status ${positive ? "adp-status-positive" : warning ? "adp-status-warning" : ""}`}
    >
      <span />
      {(value ?? t("Unknown")).split("_").join(" ")}
    </span>
  );
}
export function Notice({
  error,
  message,
}: {
  error?: Error | null;
  message?: string;
}) {
  if (!error && !message) return null;
  return (
    <div
      className={`adp-notice ${error ? "adp-notice-error" : "adp-notice-success"}`}
      role={error ? "alert" : "status"}
    >
      <strong>{error ? t("Action needs attention") : t("Saved")}</strong>
      <span>{error?.message ?? message}</span>
      {error instanceof ApiError && error.status === 409 && (
        <span>
          {t(
            "This record changed on the server. Review the current version before saving again. Your form has been preserved.",
          )}
        </span>
      )}
    </div>
  );
}
export function RemoteState({
  state,
  children,
}: {
  state: { loading: boolean; error: Error | null; retry: () => void };
  children: any;
}) {
  if (state.loading)
    return (
      <div className="adp-loading" role="status">
        <span className="adp-spinner" />
        {t("Loading current site data…")}
      </div>
    );
  if (state.error)
    return (
      <section className="adp-empty adp-empty-error">
        <Icon name="shield" size={30} />
        <h3>{t("We couldn’t load this view")}</h3>
        <p role="alert">{state.error.message}</p>
        <Button onClick={state.retry}>{t("Try again")}</Button>
      </section>
    );
  return children;
}
export function Empty({ title, children, action }: any) {
  return (
    <section className="adp-empty">
      <span className="adp-empty-icon">
        <Icon name="inventory" size={27} />
      </span>
      <h3>{title}</h3>
      <p>{children}</p>
      {action}
    </section>
  );
}
export function PageHeader({ eyebrow, title, description, action }: any) {
  return (
    <header className="adp-page-header">
      <div>
        <div className="adp-eyebrow">{eyebrow ?? t("Publisher workspace")}</div>
        <h1 id="adp-page-title" tabIndex={-1}>
          {title}
        </h1>
        {description && <p>{description}</p>}
      </div>
      {action && <div className="adp-header-actions">{action}</div>}
    </header>
  );
}
export function Field({ label, hint, children }: any) {
  return (
    <label className="adp-field">
      <span>{label}</span>
      {children}
      {hint && <small>{hint}</small>}
    </label>
  );
}
let dialogSequence = 0;
export function Modal({ title, children, onClose }: any) {
  const ref = wp.element.useRef<HTMLDialogElement | null>(null);
  const titleId = wp.element.useRef(
    `adp-dialog-title-${++dialogSequence}`,
  ).current;
  wp.element.useEffect(() => {
    const previous = document.activeElement as HTMLElement;
    ref.current?.showModal();
    const onCancel = (event: Event) => {
      event.preventDefault();
      onClose();
    };
    ref.current?.addEventListener("cancel", onCancel);
    return () => {
      ref.current?.removeEventListener("cancel", onCancel);
      previous?.focus();
    };
  }, []);
  return (
    <dialog className="adp-dialog" ref={ref} aria-labelledby={titleId}>
      <header>
        <h2 id={titleId}>{title}</h2>
        <button
          type="button"
          className="adp-icon-button"
          aria-label={t("Close dialog")}
          onClick={onClose}
        >
          <Icon name="close" />
        </button>
      </header>
      {children}
    </dialog>
  );
}
export function Pager({
  page,
  total,
  perPage = 25,
  setPage,
}: {
  page: number;
  total: number;
  perPage?: number;
  setPage: (page: number) => void;
}) {
  const pages = Math.max(1, Math.ceil(total / perPage));
  return (
    <nav className="adp-pagination" aria-label={t("Results pages")}>
      <span>
        {formatNumber(total)} {t("results")} · {t("Page")} {page} {t("of")}{" "}
        {pages}
      </span>
      <div>
        <Button disabled={page <= 1} onClick={() => setPage(page - 1)}>
          {t("Previous")}
        </Button>
        <Button disabled={page >= pages} onClick={() => setPage(page + 1)}>
          {t("Next")}
        </Button>
      </div>
    </nav>
  );
}
export function SearchFilter({
  value,
  onChange,
  placeholder = t("Search by name…"),
  children,
}: any) {
  return (
    <div className="adp-filter-bar">
      <label className="adp-search">
        <Icon name="search" size={17} />
        <span className="adp-sr-only">{placeholder}</span>
        <input
          type="search"
          placeholder={placeholder}
          value={value}
          onChange={(event: any) => onChange(event.target.value)}
        />
      </label>
      {children}
    </div>
  );
}
export function ConfirmAction({
  title,
  description,
  onConfirm,
  close,
  busy,
  error,
  destructive = false,
}: any) {
  const [reason, setReason] = wp.element.useState("");
  return (
    <Modal title={title} onClose={close}>
      <form
        onSubmit={(event: any) => {
          event.preventDefault();
          onConfirm(reason);
        }}
      >
        <p>{description}</p>
        <Field
          label={t("Reason")}
          hint={t("This reason will be recorded with the action.")}
        >
          <textarea
            required
            maxLength={1000}
            rows={3}
            value={reason}
            onChange={(event: any) => setReason(event.target.value)}
          />
        </Field>
        <Notice error={error} />
        <footer>
          <Button disabled={busy} onClick={close}>
            {t("Cancel")}
          </Button>
          <Button
            type="submit"
            disabled={busy || !reason.trim()}
            kind={destructive ? "danger" : "primary"}
          >
            {busy ? t("Working…") : title}
          </Button>
        </footer>
      </form>
    </Modal>
  );
}
