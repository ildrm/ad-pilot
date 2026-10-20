export interface Entity {
  id: string;
  name?: string;
  status?: string;
  row_version?: number;
  [key: string]: any;
}
export interface Page<T = Entity> {
  items: T[];
  total: number;
  page: number;
  per_page: number;
}
export const config =
  window.adpilotConfig ??
  (window.adpilotEditor
    ? {
        restUrl: window.adpilotEditor.restUrl,
        nonce: window.adpilotEditor.nonce,
        mode: "admin" as const,
        capabilities: [
          "adpilot_insert_blocks",
          ...(window.adpilotEditor.canViewInventory
            ? ["adpilot_view_inventory"]
            : []),
        ],
        userId: undefined,
        siteId: undefined,
      }
    : undefined);
export const t = (value: string) => wp.i18n?.__(value, "adpilot") ?? value;
export const can = (capability: string) =>
  !!config?.capabilities.includes(capability);
export const idPath = (value: string) => encodeURIComponent(value);
export class ApiError extends Error {
  constructor(
    message: string,
    public status = 0,
    public code = "network_error",
  ) {
    super(message);
  }
}
export async function request<T>(
  path: string,
  options: {
    method?: string;
    body?: unknown;
    signal?: AbortSignal;
    idempotencyKey?: string;
  } = {},
): Promise<T> {
  if (!config)
    throw new ApiError(
      t("AdPilot configuration is missing. Reload this page from WordPress."),
      0,
      "missing_configuration",
    );
  const base = new URL(config.restUrl, window.location.origin);
  if (base.origin !== window.location.origin)
    throw new ApiError(
      t("The API must use the same site origin."),
      0,
      "invalid_origin",
    );
  let url: URL;
  if (base.searchParams.has("rest_route")) {
    // WordPress without pretty permalinks stores the route in a query string.
    const relative = new URL(path, `${window.location.origin}/`);
    if (relative.origin !== window.location.origin)
      throw new ApiError(
        t("The API must use the same site origin."),
        0,
        "invalid_origin",
      );
    url = new URL(base.href);
    url.searchParams.set(
      "rest_route",
      `${base.searchParams.get("rest_route")!.replace(/\/?$/, "/")}${relative.pathname.replace(/^\/+/, "")}`,
    );
    relative.searchParams.forEach((value, key) =>
      url.searchParams.set(key, value),
    );
  } else {
    url = new URL(
      path,
      base.href.endsWith("/") ? base : new URL(base.href + "/"),
    );
  }
  if (url.origin !== window.location.origin)
    throw new ApiError(
      t("The API must use the same site origin."),
      0,
      "invalid_origin",
    );
  let response: Response;
  try {
    response = await fetch(url, {
      method: options.method ?? "GET",
      signal: options.signal,
      credentials: "same-origin",
      headers: {
        "X-WP-Nonce": config.nonce,
        ...(options.body ? { "Content-Type": "application/json" } : {}),
        ...(options.idempotencyKey
          ? { "Idempotency-Key": options.idempotencyKey }
          : {}),
      },
      body:
        options.body === undefined ? undefined : JSON.stringify(options.body),
    });
  } catch (error) {
    if (error instanceof DOMException && error.name === "AbortError")
      throw error;
    throw new ApiError(
      t(
        "The server could not be reached. Your changes are still here. Try again.",
      ),
    );
  }
  const json = await response.json().catch(() => null);
  if (!response.ok)
    throw new ApiError(
      json?.message ?? t("The server could not complete this request."),
      response.status,
      json?.code,
    );
  return json as T;
}
export const listPath = (
  path: string,
  page = 1,
  search = "",
  extra: Record<string, string> = {},
) => {
  const query = new URLSearchParams({
    page: String(page),
    per_page: "25",
    ...extra,
  });
  if (search) query.set("search", search);
  return `${path}?${query}`;
};
export function useRemote<T>(path: string | null, refresh = 0) {
  const [state, setState] = wp.element.useState<{
    data: T | null;
    loading: boolean;
    error: ApiError | null;
  }>({ data: null, loading: true, error: null });
  const [retry, setRetry] = wp.element.useState(0);
  wp.element.useEffect(() => {
    if (!path) {
      setState({ data: null, loading: false, error: null });
      return;
    }
    const controller = new AbortController();
    setState({ data: null, loading: true, error: null });
    request<T>(path, { signal: controller.signal })
      .then((data) => {
        if (!controller.signal.aborted)
          setState({ data, loading: false, error: null });
      })
      .catch((error) => {
        if (!controller.signal.aborted)
          setState({ data: null, loading: false, error });
      });
    return () => controller.abort();
  }, [path, refresh, retry]);
  return { ...state, retry: () => setRetry((previous) => previous + 1) };
}
export function useCommand(onSuccess?: (data: Entity) => void) {
  const [busy, setBusy] = wp.element.useState(false);
  const [error, setError] = wp.element.useState<Error | null>(null);
  const [message, setMessage] = wp.element.useState("");
  const inFlight = wp.element.useRef(false);
  async function run(
    path: string,
    body: unknown,
    success: string,
    method = "POST",
  ) {
    if (inFlight.current) return null;
    inFlight.current = true;
    setBusy(true);
    setError(null);
    setMessage("");
    try {
      const result = await request<Entity>(path, { method, body });
      setMessage(success);
      onSuccess?.(result);
      return result;
    } catch (failure) {
      setError(failure as Error);
      return null;
    } finally {
      inFlight.current = false;
      setBusy(false);
    }
  }
  return {
    run,
    busy,
    error,
    message,
    clear: () => {
      setError(null);
      setMessage("");
    },
  };
}
export function formatNumber(value: unknown) {
  return typeof value === "number"
    ? new Intl.NumberFormat(document.documentElement.lang || undefined).format(
        value,
      )
    : "—";
}
export function formatMoney(
  minor: unknown,
  currency: unknown,
  precision: unknown,
) {
  if (
    typeof minor !== "string" ||
    !/^(0|[1-9]\d*)$/.test(minor) ||
    typeof currency !== "string" ||
    !/^[A-Z]{3}$/.test(currency)
  )
    return "—";
  if (
    typeof precision !== "number" ||
    !Number.isInteger(precision) ||
    precision < 0 ||
    precision > 4
  )
    return `${currency} ${minor} ${t("minor units")}`;
  const digits = minor.padStart(precision + 1, "0");
  const whole = precision ? digits.slice(0, -precision) : digits;
  const fraction = precision ? digits.slice(-precision) : "";
  const separator =
    new Intl.NumberFormat(document.documentElement.lang || undefined)
      .formatToParts(1.1)
      .find((part) => part.type === "decimal")?.value ?? ".";
  return `${currency} ${new Intl.NumberFormat(document.documentElement.lang || undefined).format(BigInt(whole))}${precision ? separator + fraction : ""}`;
}
export function formatDate(value: unknown) {
  if (typeof value !== "string" || !value) return "—";
  const normalized = /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(value)
    ? value.replace(" ", "T") + "Z"
    : value;
  const date = new Date(normalized);
  return Number.isNaN(date.getTime()) ? value : date.toLocaleString();
}
