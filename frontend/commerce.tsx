import {
  Entity,
  formatDate,
  formatMoney,
  idPath,
  listPath,
  Page,
  request,
  t,
  useCommand,
  useRemote,
} from "./api";
import {
  Button,
  Empty,
  Field,
  Modal,
  Notice,
  Pager,
  RemoteState,
  Status,
} from "./ui";

export function RateForm({
  slot,
  onClose,
  onSaved,
}: {
  slot: Entity;
  onClose: () => void;
  onSaved: () => void;
}) {
  const [values, setValues] = wp.element.useState({
    currency: "",
    unit_price_minor: "",
    currency_precision: 2,
    policy_reference: "",
    quote_ttl_seconds: 600,
    hold_ttl_seconds: 600,
  });
  const [acknowledged, setAcknowledged] = wp.element.useState(false);
  const command = useCommand(onSaved);
  const update = (key: string, value: any) =>
    setValues((previous) => ({ ...previous, [key]: value }));
  return (
    <Modal title={t("Publish a fixed-window rate")} onClose={onClose}>
      <form
        onSubmit={(event: any) => {
          event.preventDefault();
          command.run(
            "admin/rate-cards",
            {
              slot_id: slot.id,
              currency: values.currency,
              unit_price_minor: values.unit_price_minor,
              parameters: {
                tax_policy: "explicitly_not_applicable",
                policy_reference: values.policy_reference,
                quote_ttl_seconds: values.quote_ttl_seconds,
                hold_ttl_seconds: values.hold_ttl_seconds,
                currency_precision: values.currency_precision,
              },
            },
            t("Fixed-window rate published."),
          );
        }}
      >
        <p>
          {slot.name ?? slot.id}.{" "}
          {t(
            "This is one fixed price for the entire selected booking window, not a daily rate. Published rate versions are immutable.",
          )}
        </p>
        <div className="adp-form-grid">
          <Field label={t("ISO currency code")}>
            <input
              required
              autoFocus
              pattern="[A-Z]{3}"
              placeholder="USD"
              maxLength={3}
              value={values.currency}
              onChange={(event: any) =>
                update("currency", event.target.value.toUpperCase())
              }
            />
          </Field>
          <Field
            label={t("Currency precision")}
            hint={t("Number of fractional digits in one major unit.")}
          >
            <select
              value={values.currency_precision}
              onChange={(event: any) =>
                update("currency_precision", Number(event.target.value))
              }
            >
              {[0, 1, 2, 3, 4].map((value) => (
                <option key={value} value={value}>
                  {value}
                </option>
              ))}
            </select>
          </Field>
        </div>
        <Field
          label={t("Price in minor units")}
          hint={t(
            "An exact integer string. For precision 2, 2500 means 25.00.",
          )}
        >
          <input
            required
            inputMode="numeric"
            pattern="(0|[1-9][0-9]{0,17})"
            maxLength={18}
            value={values.unit_price_minor}
            onChange={(event: any) =>
              update("unit_price_minor", event.target.value)
            }
          />
        </Field>
        <div className="adp-form-summary">
          {formatMoney(
            values.unit_price_minor,
            values.currency,
            values.currency_precision,
          )}{" "}
          · {t("one fixed booking window")}
        </div>
        <Field
          label={t("Tax policy reference")}
          hint={t(
            "Required evidence that this transaction class has no applicable tax. Other tax regimes need a certified adapter.",
          )}
        >
          <input
            required
            maxLength={191}
            value={values.policy_reference}
            onChange={(event: any) =>
              update("policy_reference", event.target.value)
            }
          />
        </Field>
        <label className="adp-checkbox-label adp-policy-ack">
          <input
            required
            type="checkbox"
            checked={acknowledged}
            onChange={(event: any) => setAcknowledged(event.target.checked)}
          />
          <span>
            {t(
              "I confirm that tax is explicitly not applicable under the referenced policy.",
            )}
          </span>
        </label>
        <div className="adp-form-grid">
          <Field label={t("Quote lifetime (seconds)")}>
            <input
              type="number"
              required
              min={60}
              max={3600}
              value={values.quote_ttl_seconds}
              onChange={(event: any) =>
                update("quote_ttl_seconds", Number(event.target.value))
              }
            />
          </Field>
          <Field label={t("Hold lifetime (seconds)")}>
            <input
              type="number"
              required
              min={60}
              max={3600}
              value={values.hold_ttl_seconds}
              onChange={(event: any) =>
                update("hold_ttl_seconds", Number(event.target.value))
              }
            />
          </Field>
        </div>
        <Notice error={command.error} />
        <footer>
          <Button disabled={command.busy} onClick={onClose}>
            {t("Cancel")}
          </Button>
          <Button
            type="submit"
            kind="primary"
            disabled={command.busy || !acknowledged}
          >
            {command.busy ? t("Publishing…") : t("Publish rate version")}
          </Button>
        </footer>
      </form>
    </Modal>
  );
}

export function BookingModal({
  placement,
  orgId,
  onClose,
  onOrdered,
}: {
  placement: Entity;
  orgId: string;
  onClose: () => void;
  onOrdered: () => void;
}) {
  const rates = useRemote<Page>(
    `catalog/packages?slot_id=${idPath(placement.id)}&per_page=100`,
  );
  const features = useRemote<Record<string, Entity>>(
    "public/feature-capabilities",
  );
  const [values, setValues] = wp.element.useState({
    rate_id: "",
    start_at: "",
    end_at: "",
  });
  const [quote, setQuote] = wp.element.useState<Entity | null>(null);
  const [hold, setHold] = wp.element.useState<Entity | null>(null);
  const [order, setOrder] = wp.element.useState<Entity | null>(null);
  const [error, setError] = wp.element.useState<Error | null>(null);
  const [busy, setBusy] = wp.element.useState(false);
  const [now, setNow] = wp.element.useState(Date.now());
  const lock = wp.element.useRef(false);
  const holdKey = wp.element.useRef(window.crypto.randomUUID());
  wp.element.useEffect(() => {
    const timer = setInterval(() => setNow(Date.now()), 1000);
    return () => clearInterval(timer);
  }, []);
  const availableRates =
    rates.data?.items.filter((rate) => rate.slot_id === placement.id) ?? [];
  const precision = quote?.offer?.terms?.currency_precision;
  const expires = (value: any) =>
    typeof value === "string"
      ? new Date(
          value.includes("T") ? value : value.replace(" ", "T") + "Z",
        ).getTime()
      : 0;
  const quoteExpired = !!quote && now >= expires(quote.expires_at);
  const holdExpired = !!hold && now >= expires(hold.expires_at);
  async function execute(path: string, body: unknown, key?: string) {
    if (lock.current) return null;
    lock.current = true;
    setBusy(true);
    setError(null);
    try {
      return await request<Entity>(`organizations/${idPath(orgId)}/${path}`, {
        method: "POST",
        body,
        idempotencyKey: key,
      });
    } catch (failure) {
      setError(failure as Error);
      return null;
    } finally {
      lock.current = false;
      setBusy(false);
    }
  }
  function update(key: string, value: string) {
    setValues((previous) => ({ ...previous, [key]: value }));
    setQuote(null);
    setHold(null);
    setOrder(null);
  }
  return (
    <Modal title={t("Quote & reserve a placement")} onClose={onClose}>
      <p>
        {placement.name ?? placement.id}.{" "}
        {t(
          "Quote, temporary hold, order, and verified payment are separate states. No payment is collected here.",
        )}
      </p>
      <ol className="adp-booking-steps" aria-label={t("Booking progress")}>
        {[t("Quote"), t("Hold"), t("Order")].map((label, index) => (
          <li
            key={label}
            aria-current={
              (order ? 2 : hold ? 1 : 0) === index ? "step" : undefined
            }
          >
            <span>{index + 1}</span>
            {label}
          </li>
        ))}
      </ol>
      <RemoteState state={rates}>
        {!availableRates.length ? (
          <Empty title={t("No published rate for this placement")}>
            {t(
              "The publisher must publish an applicable rate and tax policy before quoting this placement.",
            )}
          </Empty>
        ) : (
          <form
            onSubmit={async (event: any) => {
              event.preventDefault();
              if (new Date(values.end_at) <= new Date(values.start_at)) {
                setError(
                  new Error(t("End time must be later than start time.")),
                );
                return;
              }
              const result = await execute("quotes", {
                ...values,
                start_at: new Date(values.start_at).toISOString(),
                end_at: new Date(values.end_at).toISOString(),
              });
              if (result) {
                if (result.offer?.slot_id !== placement.id) {
                  setError(
                    new Error(
                      t(
                        "The quoted placement does not match your selection. Ask the publisher to review the rate.",
                      ),
                    ),
                  );
                  return;
                }
                setQuote(result);
                setHold(null);
                setOrder(null);
                holdKey.current = window.crypto.randomUUID();
              }
            }}
          >
            <Field label={t("Published fixed-window rate")}>
              <select
                required
                disabled={!!hold}
                value={values.rate_id}
                onChange={(event: any) => update("rate_id", event.target.value)}
              >
                <option value="">{t("Choose published rate")}</option>
                {availableRates.map((rate) => (
                  <option key={rate.id} value={rate.id}>
                    {formatMoney(
                      rate.unit_price_minor,
                      rate.currency,
                      rate.parameters?.currency_precision,
                    )}{" "}
                    · {t("entire booking window")}
                  </option>
                ))}
              </select>
            </Field>
            <div className="adp-form-grid">
              <Field
                label={t("Start time")}
                hint={t("Your browser’s local time zone.")}
              >
                <input
                  required
                  type="datetime-local"
                  disabled={!!hold}
                  value={values.start_at}
                  onChange={(event: any) =>
                    update("start_at", event.target.value)
                  }
                />
              </Field>
              <Field label={t("End time")}>
                <input
                  required
                  type="datetime-local"
                  disabled={!!hold}
                  value={values.end_at}
                  onChange={(event: any) =>
                    update("end_at", event.target.value)
                  }
                />
              </Field>
            </div>
            <Button type="submit" disabled={busy || !!hold || !orgId}>
              {busy
                ? t("Working…")
                : quote
                  ? t("Refresh quote")
                  : t("Request quote")}
            </Button>
          </form>
        )}
      </RemoteState>
      {quote && (
        <section className="adp-booking-summary">
          <div className="adp-section-heading">
            <h3>{t("Server quote")}</h3>
            <Status value={quoteExpired && !hold ? "expired" : quote.status} />
          </div>
          <dl className="adp-report-list">
            <div>
              <dt>{t("Total for the entire window")}</dt>
              <dd>
                {formatMoney(quote.total_minor, quote.currency, precision)}
              </dd>
            </div>
            <div>
              <dt>{t("Quote expires")}</dt>
              <dd className="adp-small">{formatDate(quote.expires_at)}</dd>
            </div>
          </dl>
          <p className="adp-small">
            {t("Tax policy")}:{" "}
            {quote.offer?.terms?.tax_policy === "explicitly_not_applicable"
              ? t("Explicitly not applicable")
              : t("See published terms")}
            . {t("Policy reference")}:{" "}
            {quote.offer?.terms?.policy_reference ?? "—"}
          </p>
          {!hold && (
            <Button
              kind="primary"
              disabled={busy || quoteExpired}
              onClick={async () => {
                const result = await execute(
                  "holds",
                  { quote_id: quote.id },
                  holdKey.current,
                );
                if (result) setHold(result);
              }}
            >
              {busy ? t("Reserving…") : t("Acquire temporary hold")}
            </Button>
          )}
          {quoteExpired && !hold && (
            <p role="status">
              {t("The quote expired. Request a fresh quote before reserving.")}
            </p>
          )}
        </section>
      )}
      {hold && (
        <section className="adp-booking-summary">
          <div className="adp-section-heading">
            <h3>{t("Temporary inventory hold")}</h3>
            <Status value={holdExpired ? "expired" : hold.status} />
          </div>
          <p>
            {t("Hold expires")}: {formatDate(hold.expires_at)}.
          </p>
          <p className="adp-small">
            {t(
              "A temporary hold is not a confirmed commercial reservation or a paid order.",
            )}
          </p>
          {!order && (
            <div className="adp-actions">
              <Button
                kind="primary"
                disabled={busy || holdExpired || hold.status !== "active"}
                onClick={async () => {
                  const result = await execute("orders", { hold_id: hold.id });
                  if (result) {
                    setOrder(result);
                    onOrdered();
                  }
                }}
              >
                {busy ? t("Creating…") : t("Create unpaid order")}
              </Button>
              <Button
                disabled={busy || hold.status !== "active"}
                onClick={async () => {
                  const result = await execute(
                    `holds/${idPath(hold.id)}/actions/release`,
                    {},
                  );
                  if (result) {
                    setHold(null);
                    setQuote(null);
                  }
                }}
              >
                {t("Release hold")}
              </Button>
            </div>
          )}
        </section>
      )}
      {order && (
        <section className="adp-booking-summary">
          <div className="adp-section-heading">
            <h3>{t("Order created")}</h3>
            <Status value={order.status} />
          </div>
          <p>
            {t("Order")}: <code>{order.id}</code>
          </p>
          <p>
            <strong>
              {formatMoney(order.total_minor, order.currency, precision)}
            </strong>{" "}
            · {t("Paid")}:{" "}
            {formatMoney(order.paid_minor, order.currency, precision)}
          </p>
          <div className="adp-future-note">
            <div>
              <strong>{t("Checkout unavailable")}</strong>
              <p>
                {features.data?.checkout?.reason ??
                  t(
                    "A verified commerce adapter and financial certification are required before payment can be accepted.",
                  )}
              </p>
            </div>
          </div>
        </section>
      )}
      <Notice error={error} />
      <footer>
        <Button disabled={busy} onClick={onClose}>
          {t("Done")}
        </Button>
      </footer>
    </Modal>
  );
}

export function OrderList({
  orgId,
  refresh,
}: {
  orgId: string;
  refresh: number;
}) {
  const [page, setPage] = wp.element.useState(1);
  const orders = useRemote<Page>(
    orgId ? listPath(`organizations/${idPath(orgId)}/orders`, page) : null,
    refresh,
  );
  if (!orgId)
    return (
      <Empty title={t("Choose an organization")}>
        {t("Orders are visible only within the selected organization.")}
      </Empty>
    );
  return (
    <RemoteState state={orders}>
      {orders.data?.items.length ? (
        <>
          <div className="adp-table-scroll">
            <table className="adp-table">
              <thead>
                <tr>
                  <th>{t("Order")}</th>
                  <th>{t("Payment state")}</th>
                  <th>{t("Exact order total")}</th>
                  <th>{t("Paid")}</th>
                </tr>
              </thead>
              <tbody>
                {orders.data.items.map((order) => (
                  <tr key={order.id}>
                    <th scope="row">
                      <code>{order.id}</code>
                    </th>
                    <td>
                      <Status value={order.status} />
                    </td>
                    <td>
                      {formatMoney(
                        order.total_minor,
                        order.currency,
                        order.currency_precision,
                      )}
                    </td>
                    <td>
                      {formatMoney(
                        order.paid_minor,
                        order.currency,
                        order.currency_precision,
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          <Pager page={page} total={orders.data.total} setPage={setPage} />
          <p className="adp-provenance" style={{ padding: "0 20px 20px" }}>
            {t(
              "When currency precision is absent, exact amounts are labeled in minor units. Pending payment does not imply eligible campaign delivery.",
            )}
          </p>
        </>
      ) : (
        <Empty title={t("No orders yet")}>
          {t(
            "Request a published rate quote, acquire a temporary hold, then create an order. Payment remains separately verified.",
          )}
        </Empty>
      )}
    </RemoteState>
  );
}
