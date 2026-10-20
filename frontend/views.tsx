import {
  can,
  Entity,
  formatDate,
  formatNumber,
  idPath,
  listPath,
  Page,
  t,
  useCommand,
  useRemote,
} from "./api";
import {
  Button,
  ConfirmAction,
  Empty,
  Field,
  Icon,
  Modal,
  Notice,
  PageHeader,
  Pager,
  RemoteState,
  SearchFilter,
  Status,
} from "./ui";
import { BookingModal, OrderList, RateForm } from "./commerce";
import { PlacementPicker } from "./placement-picker";

type Navigate = (route: string) => void;
export function Overview({ navigate }: { navigate: Navigate }) {
  const report = useRemote<Entity>("admin/reports/overview");
  const data = report.data;
  return (
    <>
      <PageHeader
        title={t("A clear view of your advertising.")}
        description={t(
          "Your inventory, campaigns, and next decisions in one place.",
        )}
        action={
          <Button onClick={report.retry} icon="clock">
            {t("Refresh data")}
          </Button>
        }
      />
      <RemoteState state={report}>
        <div className="adp-metrics">
          {[
            [
              "slots",
              t("Configured slots"),
              t("Persistent inventory positions"),
              "inventory",
            ],
            [
              "campaigns",
              t("Campaigns"),
              t("All campaign states"),
              "campaigns",
            ],
            [
              "pending_reviews",
              t("Awaiting review"),
              t("Creative decisions to make"),
              "creatives",
            ],
            [
              "served",
              t("Served decisions"),
              t("Delivery selections, not impressions"),
              "reports",
            ],
          ].map(([key, label, detail, route]) => (
            <button
              className="adp-metric"
              key={key}
              onClick={() => navigate(route)}
            >
              <span>
                {label}
                <Icon name={route} />
              </span>
              <strong>{formatNumber(data?.[key])}</strong>
              <small>{detail}</small>
            </button>
          ))}
        </div>
        <div className="adp-overview-grid">
          <section className="adp-panel adp-overview-inventory">
            <div className="adp-section-heading">
              <div>
                <div className="adp-eyebrow">{t("Your foundation")}</div>
                <h2>{t("Inventory that stays organized.")}</h2>
              </div>
              <Icon name="inventory" size={28} />
            </div>
            <p>
              {t(
                "Create reusable zones, generate stable slots, and publish a compact layout for your site.",
              )}
            </p>
            <dl className="adp-inline-facts">
              <div>
                <dt>{t("Zones")}</dt>
                <dd>{formatNumber(data?.zones)}</dd>
              </div>
              <div>
                <dt>{t("Logical slots")}</dt>
                <dd>{formatNumber(data?.slots)}</dd>
              </div>
            </dl>
            <div className="adp-actions">
              <Button
                kind="primary"
                icon="studio"
                onClick={() => navigate("studio")}
              >
                {t("Open Layout Studio")}
              </Button>
              <Button onClick={() => navigate("inventory")}>
                {t("View inventory")}
              </Button>
            </div>
          </section>
          <section className="adp-panel">
            <div className="adp-section-heading">
              <div>
                <div className="adp-eyebrow">{t("Next decisions")}</div>
                <h2>{t("Keep things moving")}</h2>
              </div>
            </div>
            <a className="adp-task-link" href="#creatives">
              <Icon name="shield" />
              <span>
                <strong>{t("Review creative submissions")}</strong>
                <small>
                  {formatNumber(data?.pending_reviews)}{" "}
                  {t("waiting for an exact-version decision")}
                </small>
              </span>
              <Icon name="arrow" />
            </a>
            <a className="adp-task-link" href="#campaigns">
              <Icon name="campaigns" />
              <span>
                <strong>{t("Manage campaign delivery")}</strong>
                <small>{t("Inspect lifecycle and eligibility")}</small>
              </span>
              <Icon name="arrow" />
            </a>
            <a className="adp-task-link" href="#settings">
              <Icon name="settings" />
              <span>
                <strong>{t("Check system health")}</strong>
                <small>{t("Configuration and delivery diagnostics")}</small>
              </span>
              <Icon name="arrow" />
            </a>
          </section>
        </div>
        <div className="adp-provenance">
          <Icon name="clock" size={15} />
          {t("Site aggregates")} ·{" "}
          {data?.updated_at
            ? `${t("Updated")} ${formatDate(data.updated_at)}`
            : t("Refresh to request current aggregates")}
          .{" "}
          {t(
            "Configured capacity and observed delivery are different measures.",
          )}
        </div>
      </RemoteState>
    </>
  );
}

export function Inventory() {
  const [page, setPage] = wp.element.useState(1);
  const [search, setSearch] = wp.element.useState("");
  const [tab, setTab] = wp.element.useState("zones");
  const [dialog, setDialog] = wp.element.useState<string | null>(null);
  const [revision, setRevision] = wp.element.useState(0);
  const [rateSlot, setRateSlot] = wp.element.useState<Entity | null>(null);
  const items = useRemote<Page>(
    listPath(`admin/${tab}`, page, search),
    revision,
  );
  return (
    <>
      <PageHeader
        title={t("Inventory")}
        description={t(
          "One registry for zones, containers, and stable placement identities.",
        )}
        action={
          can("adpilot_manage_inventory") && (
            <Button
              kind="primary"
              icon="plus"
              onClick={() =>
                setDialog(tab === "containers" ? "container" : "zone")
              }
            >
              {tab === "containers" ? t("Create container") : t("Create zone")}
            </Button>
          )
        }
      />
      <section className="adp-panel adp-panel-table">
        <div
          className="adp-tabs"
          role="tablist"
          aria-label={t("Inventory type")}
        >
          {["zones", "containers", "slots"].map((key) => (
            <button
              key={key}
              id={`adp-inventory-${key}`}
              role="tab"
              aria-selected={tab === key}
              aria-controls="adp-inventory-results"
              tabIndex={tab === key ? 0 : -1}
              onKeyDown={(event: any) => {
                if (
                  ["ArrowLeft", "ArrowRight", "Home", "End"].includes(event.key)
                ) {
                  event.preventDefault();
                  const tabs = ["zones", "containers", "slots"];
                  const index =
                    event.key === "Home"
                      ? 0
                      : event.key === "End"
                        ? 2
                        : (tabs.indexOf(tab) +
                            (event.key === "ArrowRight" ? 1 : 2)) %
                          3;
                  setTab(tabs[index]);
                  setPage(1);
                  document
                    .getElementById(`adp-inventory-${tabs[index]}`)
                    ?.focus();
                }
              }}
              onClick={() => {
                setTab(key);
                setPage(1);
                setSearch("");
              }}
            >
              {t(key[0].toUpperCase() + key.slice(1))}
            </button>
          ))}
        </div>
        <SearchFilter
          value={search}
          onChange={(value: string) => {
            setSearch(value);
            setPage(1);
          }}
          placeholder={t("Search inventory…")}
        >
          <span className="adp-muted">
            {t("Site inventory")} <Icon name="shield" size={14} />
          </span>
        </SearchFilter>
        <div
          id="adp-inventory-results"
          role="tabpanel"
          aria-labelledby={`adp-inventory-${tab}`}
        >
          <RemoteState state={items}>
            {items.data?.items.length ? (
              <>
                <div className="adp-table-scroll">
                  <table className="adp-table">
                    <thead>
                      <tr>
                        <th scope="col">
                          {tab === "slots" ? t("Slot identity") : t("Name")}
                        </th>
                        <th scope="col">{t("Status")}</th>
                        <th scope="col">
                          {tab === "zones" ? t("Description") : t("Zone")}
                        </th>
                        <th scope="col">
                          {tab === "containers"
                            ? t("Configured slots")
                            : tab === "slots"
                              ? t("Ordinal")
                              : t("Version")}
                        </th>
                        {tab === "slots" && can("adpilot_manage_rates") && (
                          <th>{t("Fixed rates")}</th>
                        )}
                      </tr>
                    </thead>
                    <tbody>
                      {items.data.items.map((item) => (
                        <tr key={item.id}>
                          <th scope="row">
                            <strong>{item.name ?? item.id}</strong>
                            <small className="adp-record-id">{item.id}</small>
                          </th>
                          <td>
                            <Status value={item.status} />
                          </td>
                          <td>
                            {tab === "zones"
                              ? item.description || "—"
                              : (item.zone_name ?? item.zone_id)}
                          </td>
                          <td>
                            {formatNumber(
                              tab === "containers"
                                ? item.slot_count
                                : tab === "slots"
                                  ? item.ordinal
                                  : item.row_version,
                            )}
                          </td>
                          {tab === "slots" && can("adpilot_manage_rates") && (
                            <td>
                              <Button
                                disabled={item.status !== "published"}
                                title={
                                  item.status !== "published"
                                    ? t(
                                        "Publish the slot in a layout before publishing a rate.",
                                      )
                                    : undefined
                                }
                                onClick={() => setRateSlot(item)}
                              >
                                {t("Publish fixed rate")}
                              </Button>
                            </td>
                          )}
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
                <Pager page={page} total={items.data.total} setPage={setPage} />
              </>
            ) : (
              <Empty
                title={
                  search
                    ? t("No matching inventory")
                    : t("Build your first placement zone")
                }
              >
                {search
                  ? t("Try a different search or remove your filters.")
                  : t(
                      "A zone groups your placements. Add a container to generate stable slots, then arrange them in Layout Studio.",
                    )}
              </Empty>
            )}
          </RemoteState>
        </div>
      </section>
      <p className="adp-provenance">
        {t(
          "Logical slot identities remain stable across responsive presentation changes. A configured slot is not a reservation or a measured impression.",
        )}
      </p>
      {dialog && (
        <InventoryForm
          type={dialog}
          onClose={() => setDialog(null)}
          onSaved={() => {
            setRevision((value) => value + 1);
            setDialog(null);
          }}
        />
      )}{" "}
      {rateSlot && (
        <RateForm
          slot={rateSlot}
          onClose={() => setRateSlot(null)}
          onSaved={() => {
            setRateSlot(null);
            setRevision((value) => value + 1);
          }}
        />
      )}
    </>
  );
}

export function InventoryForm({
  type,
  onClose,
  onSaved,
  zoneId = "",
}: {
  type: string;
  onClose: () => void;
  onSaved: (item: Entity) => void;
  zoneId?: string;
}) {
  const zones = useRemote<Page>(
    type === "container" ? "admin/zones?per_page=100" : null,
  );
  const [values, setValues] = wp.element.useState({
    name: "",
    description: "",
    zone_id: zoneId,
    slot_count: 100,
    columns: 5,
    rows: 20,
    gap: 8,
    aspect_ratio: "4/1",
  });
  const command = useCommand(onSaved);
  const update = (key: string, value: any) =>
    setValues((previous) => ({ ...previous, [key]: value }));
  const container = type === "container";
  return (
    <Modal
      title={container ? t("Create a slot container") : t("Create a zone")}
      onClose={onClose}
    >
      <form
        onSubmit={(event: any) => {
          event.preventDefault();
          command.run(
            `admin/${container ? "containers" : "zones"}`,
            container
              ? {
                  name: values.name,
                  zone_id: values.zone_id,
                  slot_count: values.slot_count,
                  columns: values.columns,
                  rows: values.rows,
                  gap: values.gap,
                  aspect_ratio: values.aspect_ratio,
                }
              : { name: values.name, description: values.description },
            t("Inventory created."),
          );
        }}
      >
        <p className="adp-form-intro">
          {container
            ? t(
                "Generate persistent slot identities in one compact container. Preview cells do not serve ads or create measurement.",
              )
            : t("Give this part of your site a clear, reusable name.")}
        </p>
        <Field label={t("Name")}>
          <input
            required
            autoFocus
            maxLength={160}
            value={values.name}
            placeholder={container ? t("e.g. Header grid") : t("e.g. Homepage")}
            onChange={(event: any) => update("name", event.target.value)}
          />
        </Field>
        {container ? (
          <>
            <Field label={t("Zone")}>
              <select
                required
                value={values.zone_id}
                onChange={(event: any) => update("zone_id", event.target.value)}
              >
                <option value="">
                  {zones.loading ? t("Loading zones…") : t("Choose a zone")}
                </option>
                {zones.data?.items.map((zone) => (
                  <option value={zone.id} key={zone.id}>
                    {zone.name}
                  </option>
                ))}
              </select>
            </Field>
            <Notice error={zones.error} />
            <div className="adp-form-grid">
              <Field
                label={t("Logical slots")}
                hint={t("Commercial availability is evaluated separately.")}
              >
                <input
                  required
                  type="number"
                  min={1}
                  max={1000}
                  value={values.slot_count}
                  onChange={(event: any) => {
                    const count = Number(event.target.value);
                    setValues((previous) => ({
                      ...previous,
                      slot_count: count,
                      rows: Math.ceil(count / previous.columns),
                    }));
                  }}
                />
              </Field>
              <Field label={t("Columns")}>
                <input
                  required
                  type="number"
                  min={1}
                  max={20}
                  value={values.columns}
                  onChange={(event: any) => {
                    const columns = Number(event.target.value);
                    setValues((previous) => ({
                      ...previous,
                      columns,
                      rows: Math.ceil(
                        previous.slot_count / Math.max(columns, 1),
                      ),
                    }));
                  }}
                />
              </Field>
              <Field label={t("Gap (px)")}>
                <input
                  required
                  type="number"
                  min={0}
                  max={64}
                  value={values.gap}
                  onChange={(event: any) =>
                    update("gap", Number(event.target.value))
                  }
                />
              </Field>
              <Field label={t("Cell aspect ratio")}>
                <select
                  value={values.aspect_ratio}
                  onChange={(event: any) =>
                    update("aspect_ratio", event.target.value)
                  }
                >
                  <option value="4/1">4:1 · {t("Banner")}</option>
                  <option value="1/1">1:1 · {t("Square")}</option>
                  <option value="3/2">3:2 · {t("Landscape")}</option>
                </select>
              </Field>
            </div>
            <div className="adp-form-summary">
              <strong>{formatNumber(values.slot_count)}</strong>{" "}
              {t("logical slots")} · {values.columns} {t("columns")} ×{" "}
              {values.rows} {t("rows")}
            </div>
          </>
        ) : (
          <Field label={t("Description")}>
            <textarea
              rows={3}
              maxLength={1000}
              value={values.description}
              onChange={(event: any) =>
                update("description", event.target.value)
              }
            />
          </Field>
        )}
        <Notice error={command.error} />
        <footer>
          <Button disabled={command.busy} onClick={onClose}>
            {t("Cancel")}
          </Button>
          <Button
            kind="primary"
            type="submit"
            disabled={command.busy || (container && !values.zone_id)}
          >
            {command.busy
              ? t("Creating…")
              : container
                ? t("Create container & slots")
                : t("Create zone")}
          </Button>
        </footer>
      </form>
    </Modal>
  );
}

export function Campaigns({
  orgId,
  portal,
}: {
  orgId: string;
  portal: boolean;
}) {
  const [page, setPage] = wp.element.useState(1),
    [search, setSearch] = wp.element.useState(""),
    [revision, setRevision] = wp.element.useState(0);
  const [creating, setCreating] = wp.element.useState(false);
  const [managingOrgs, setManagingOrgs] = wp.element.useState(false);
  const [action, setAction] = wp.element.useState<{
    item: Entity;
    key: string;
  } | null>(null);
  const items = useRemote<Page>(
    portal
      ? orgId
        ? listPath(`organizations/${idPath(orgId)}/campaigns`, page, search)
        : null
      : listPath("admin/campaigns", page, search),
    revision,
  );
  const command = useCommand(() => {
    setAction(null);
    setRevision((value) => value + 1);
  });
  return (
    <>
      <PageHeader
        eyebrow={portal ? t("Advertiser workspace") : undefined}
        title={t("Campaigns")}
        description={t(
          "Track campaign state independently from creative approval, payment, and delivery.",
        )}
        action={
          <>
            {!portal && can("adpilot_manage_organization_status") && (
              <Button onClick={() => setManagingOrgs(true)}>
                {t("Advertiser organizations")}
              </Button>
            )}
            {(portal || can("adpilot_manage_campaigns")) && (
              <Button
                kind="primary"
                icon="plus"
                disabled={portal && !orgId}
                onClick={() => setCreating(true)}
              >
                {t("Create campaign")}
              </Button>
            )}
          </>
        }
      />
      {portal && !orgId ? (
        <Empty title={t("Choose an organization")}>
          {t(
            "Select your organization above or create one in Organization to manage campaigns.",
          )}
        </Empty>
      ) : (
        <section className="adp-panel adp-panel-table">
          <SearchFilter
            value={search}
            onChange={(value: string) => {
              setSearch(value);
              setPage(1);
            }}
            placeholder={t("Search campaigns…")}
          />
          <Notice error={command.error} message={command.message} />
          <RemoteState state={items}>
            {items.data?.items.length ? (
              <>
                <div className="adp-table-scroll">
                  <table className="adp-table">
                    <thead>
                      <tr>
                        <th>{t("Campaign")}</th>
                        <th>{t("Lifecycle")}</th>
                        <th>{t("Approval")}</th>
                        <th>{t("Entitlement")}</th>
                        <th>{t("Starts")}</th>
                        <th>{t("Ends")}</th>
                        <th>{t("Actions")}</th>
                      </tr>
                    </thead>
                    <tbody>
                      {items.data.items.map((item) => (
                        <tr key={item.id}>
                          <th scope="row">
                            <strong>{item.name}</strong>
                            <small className="adp-record-id">{item.id}</small>
                          </th>
                          <td>
                            <Status value={item.status} />
                          </td>
                          <td>
                            <Status value={item.approval_status} />
                          </td>
                          <td>
                            <Status value={item.financial_status} />
                          </td>
                          <td>{formatDate(item.start_at)}</td>
                          <td>{formatDate(item.end_at)}</td>
                          <td>
                            <div className="adp-table-actions">
                              {!portal &&
                                can("adpilot_manage_campaigns") &&
                                item.status === "draft" &&
                                item.financial_status === "unfunded" && (
                                  <Button
                                    onClick={() =>
                                      setAction({ item, key: "schedule" })
                                    }
                                  >
                                    {t("Manual activation")}
                                  </Button>
                                )}
                              {!portal &&
                                can("adpilot_manage_campaigns") &&
                                ["active", "paused"].includes(
                                  item.status ?? "",
                                ) && (
                                  <Button
                                    disabled={command.busy}
                                    onClick={() =>
                                      setAction({
                                        item,
                                        key:
                                          item.status === "paused"
                                            ? "resume"
                                            : "pause",
                                      })
                                    }
                                  >
                                    {item.status === "paused"
                                      ? t("Resume")
                                      : t("Pause")}
                                  </Button>
                                )}
                              {!portal &&
                                can("adpilot_emergency_stop") &&
                                !["stopped", "suspended"].includes(
                                  item.status ?? "",
                                ) && (
                                  <Button
                                    kind="text-danger"
                                    onClick={() =>
                                      setAction({ item, key: "emergency-stop" })
                                    }
                                  >
                                    {t("Stop")}
                                  </Button>
                                )}
                              {(portal ||
                                (!can("adpilot_manage_campaigns") &&
                                  !can("adpilot_emergency_stop"))) && (
                                <span className="adp-muted">
                                  {t("View only")}
                                </span>
                              )}
                            </div>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
                <Pager page={page} total={items.data.total} setPage={setPage} />
              </>
            ) : (
              <Empty title={t("No campaigns yet")}>
                {t(
                  "Create a campaign with a published placement, a creative, and a defined schedule. The server evaluates all delivery gates separately.",
                )}
              </Empty>
            )}
          </RemoteState>
        </section>
      )}
      {managingOrgs && (
        <OrganizationManager onClose={() => setManagingOrgs(false)} />
      )}{" "}
      {creating && (
        <CampaignForm
          portal={portal}
          orgId={orgId}
          onClose={() => setCreating(false)}
          onSaved={() => {
            setCreating(false);
            setRevision((value) => value + 1);
          }}
        />
      )}{" "}
      {action && (
        <ConfirmAction
          title={
            action.key === "schedule"
              ? t("Activate manual entitlement")
              : action.key === "emergency-stop"
                ? t("Emergency stop")
                : action.key === "pause"
                  ? t("Pause campaign")
                  : t("Resume campaign")
          }
          description={`${action.item.name}. ${action.key === "schedule" ? t("Requires a verified organization, an approved creative version, and an enabled manual entitlement policy. This creates a complimentary reservation, not a payment.") : t("The server will verify the current state and your permission before applying this action. No financial adjustment is implied.")}`}
          close={() => setAction(null)}
          busy={command.busy}
          error={command.error}
          destructive={action.key === "emergency-stop"}
          onConfirm={(reason: string) =>
            command.run(
              `admin/campaigns/${idPath(action.item.id)}/actions/${action.key}`,
              { reason, row_version: action.item.row_version },
              t("Campaign action completed."),
            )
          }
        />
      )}
    </>
  );
}

function OrganizationManager({ onClose }: { onClose: () => void }) {
  const [page, setPage] = wp.element.useState(1),
    [revision, setRevision] = wp.element.useState(0);
  const [name, setName] = wp.element.useState("");
  const [decision, setDecision] = wp.element.useState<{
    item: Entity;
    action: string;
  } | null>(null);
  const orgs = useRemote<Page>(listPath("admin/organizations", page), revision);
  const command = useCommand(() => {
    setDecision(null);
    setRevision((value) => value + 1);
    setName("");
  });
  return (
    <>
      <Modal title={t("Advertiser organizations")} onClose={onClose}>
        <p>
          {t(
            "Verification and suspension are audited organization decisions. Existing memberships remain the authority for advertiser actions.",
          )}
        </p>
        <form
          onSubmit={(event: any) => {
            event.preventDefault();
            command.run(
              "organizations",
              { display_name: name },
              t("Organization created with your owner membership."),
            );
          }}
        >
          <Field label={t("New organization name")}>
            <input
              value={name}
              required
              maxLength={160}
              onChange={(event: any) => setName(event.target.value)}
            />
          </Field>
          <Button type="submit" disabled={command.busy} icon="plus">
            {t("Create organization")}
          </Button>
        </form>
        <Notice error={command.error} message={command.message} />
        <RemoteState state={orgs}>
          {orgs.data?.items.length ? (
            <>
              <div className="adp-table-scroll">
                <table className="adp-table">
                  <thead>
                    <tr>
                      <th>{t("Organization")}</th>
                      <th>{t("Status")}</th>
                      <th>{t("Actions")}</th>
                    </tr>
                  </thead>
                  <tbody>
                    {orgs.data.items.map((item) => (
                      <tr key={item.id}>
                        <th scope="row">{item.display_name ?? item.name}</th>
                        <td>
                          <Status value={item.status} />
                        </td>
                        <td>
                          <div className="adp-table-actions">
                            {item.status !== "verified" && (
                              <Button
                                onClick={() =>
                                  setDecision({ item, action: "verify" })
                                }
                              >
                                {t("Verify")}
                              </Button>
                            )}
                            {item.status !== "suspended" && (
                              <Button
                                kind="text-danger"
                                onClick={() =>
                                  setDecision({ item, action: "suspend" })
                                }
                              >
                                {t("Suspend")}
                              </Button>
                            )}
                          </div>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
              <Pager page={page} total={orgs.data.total} setPage={setPage} />
            </>
          ) : (
            <Empty title={t("No advertiser organizations")}>
              {t(
                "Create an organization to provision an advertiser workspace.",
              )}
            </Empty>
          )}
        </RemoteState>
        <footer>
          <Button onClick={onClose}>{t("Done")}</Button>
        </footer>
      </Modal>
      {decision && (
        <ConfirmAction
          title={
            decision.action === "verify"
              ? t("Verify organization")
              : t("Suspend organization")
          }
          description={`${decision.item.display_name ?? decision.item.name}. ${decision.action === "suspend" ? t("Suspension blocks eligible advertiser operations and delivery.") : t("Confirm that your organization verification requirements have been satisfied.")}`}
          close={() => setDecision(null)}
          busy={command.busy}
          error={command.error}
          destructive={decision.action === "suspend"}
          onConfirm={(reason: string) =>
            command.run(
              `admin/organizations/${idPath(decision.item.id)}/actions/${decision.action}`,
              { reason, row_version: decision.item.row_version },
              t("Organization status updated."),
            )
          }
        />
      )}
    </>
  );
}

function CampaignForm({ portal, orgId, onClose, onSaved }: any) {
  const organizations = useRemote<Page | Entity[]>(
    !portal ? "my-organizations" : null,
  );
  const [org, setOrg] = wp.element.useState(orgId ?? "");
  const creatives = useRemote<Page>(
    org ? `organizations/${idPath(org)}/creatives?per_page=100` : null,
  );
  const [values, setValues] = wp.element.useState({
    name: "",
    slot_id: "",
    creative_id: "",
    start_at: "",
    end_at: "",
  });
  const [localError, setLocalError] = wp.element.useState<Error | null>(null);
  const command = useCommand(onSaved);
  const update = (key: string, value: string) =>
    setValues((previous) => ({ ...previous, [key]: value }));
  const orgItems = Array.isArray(organizations.data)
    ? organizations.data
    : organizations.data?.items;
  return (
    <Modal title={t("Create campaign")} onClose={onClose}>
      <form
        onSubmit={(event: any) => {
          event.preventDefault();
          setLocalError(null);
          if (new Date(values.end_at) <= new Date(values.start_at)) {
            setLocalError(
              new Error(t("End time must be later than start time.")),
            );
            return;
          }
          command.run(
            `organizations/${idPath(org)}/campaigns`,
            {
              ...values,
              start_at: new Date(values.start_at).toISOString(),
              end_at: new Date(values.end_at).toISOString(),
            },
            t(
              "Campaign created. Delivery remains subject to approval and eligibility.",
            ),
          );
        }}
      >
        <p className="adp-form-intro">
          {t(
            "Create a scheduled campaign draft. This does not reserve inventory, collect payment, or guarantee delivery.",
          )}
        </p>
        {!portal && (
          <Field label={t("Organization")}>
            <select
              required
              value={org}
              onChange={(event: any) => {
                setOrg(event.target.value);
                update("creative_id", "");
              }}
            >
              <option value="">{t("Choose organization")}</option>
              {orgItems?.map((item) => (
                <option key={item.id} value={item.id}>
                  {item.display_name ?? item.name}
                </option>
              ))}
            </select>
          </Field>
        )}
        <Field label={t("Campaign name")}>
          <input
            required
            autoFocus
            maxLength={160}
            value={values.name}
            onChange={(event: any) => update("name", event.target.value)}
          />
        </Field>
        <PlacementPicker
          value={values.slot_id}
          onChange={(value: string) => update("slot_id", value)}
        />
        <Field
          label={t("Creative")}
          hint={t("Create and submit your creative from the Creatives screen.")}
        >
          <select
            required
            value={values.creative_id}
            onChange={(event: any) => update("creative_id", event.target.value)}
          >
            <option value="">
              {creatives.loading && org
                ? t("Loading creatives…")
                : t("Choose creative")}
            </option>
            {creatives.data?.items.map((item) => (
              <option key={item.id} value={item.id}>
                {item.name} · {(item.status ?? "").split("_").join(" ")}
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
              value={values.start_at}
              onChange={(event: any) => update("start_at", event.target.value)}
            />
          </Field>
          <Field label={t("End time")}>
            <input
              required
              type="datetime-local"
              value={values.end_at}
              onChange={(event: any) => update("end_at", event.target.value)}
            />
          </Field>
        </div>
        <Notice
          error={
            localError ??
            command.error ??
            creatives.error ??
            organizations.error
          }
        />
        <footer>
          <Button disabled={command.busy} onClick={onClose}>
            {t("Cancel")}
          </Button>
          <Button
            type="submit"
            kind="primary"
            disabled={
              command.busy || !org || !values.creative_id || !values.slot_id
            }
          >
            {command.busy ? t("Creating…") : t("Create draft campaign")}
          </Button>
        </footer>
      </form>
    </Modal>
  );
}

export function Creatives({
  orgId,
  portal,
}: {
  orgId: string;
  portal: boolean;
}) {
  const [page, setPage] = wp.element.useState(1),
    [revision, setRevision] = wp.element.useState(0),
    [creating, setCreating] = wp.element.useState(false);
  const [decision, setDecision] = wp.element.useState<{
    item: Entity;
    action: string;
  } | null>(null);
  const items = useRemote<Page>(
    portal
      ? orgId
        ? listPath(`organizations/${idPath(orgId)}/creatives`, page)
        : null
      : listPath("admin/creative-reviews", page),
    revision,
  );
  const command = useCommand(() => {
    setDecision(null);
    setRevision((value) => value + 1);
  });
  return (
    <>
      <PageHeader
        eyebrow={portal ? t("Advertiser workspace") : undefined}
        title={portal ? t("Creatives") : t("Creative moderation")}
        description={
          portal
            ? t(
                "Prepare safe creative material and submit the exact version for review.",
              )
            : t(
                "Review the submitted content and its immutable digest before making a decision.",
              )
        }
        action={
          portal && (
            <Button
              kind="primary"
              icon="plus"
              disabled={!orgId}
              onClick={() => setCreating(true)}
            >
              {t("Create creative")}
            </Button>
          )
        }
      />
      <Notice error={command.error} message={command.message} />
      {portal && !orgId ? (
        <Empty title={t("Choose an organization")}>
          {t("Choose your organization above to view its creative library.")}
        </Empty>
      ) : (
        <RemoteState state={items}>
          {items.data?.items.length ? (
            <>
              <div className="adp-creative-list">
                {items.data.items.map((item) => (
                  <article className="adp-panel adp-creative" key={item.id}>
                    <div className="adp-creative-content">
                      <span className="adp-eyebrow">
                        {portal
                          ? t("Creative material")
                          : t("Submitted version")}
                      </span>
                      <h2>
                        {item.name ?? item.title ?? t("Untitled creative")}
                      </h2>
                      <div
                        className="adp-creative-preview"
                        aria-label={t("Text creative preview")}
                      >
                        <span>{t("Advertisement · preview only")}</span>
                        <h3>{item.title ?? item.name}</h3>
                        {item.body && <p>{item.body}</p>}
                        {item.destination_url && (
                          <small>{item.destination_url}</small>
                        )}
                      </div>
                      <small className="adp-record-id">
                        {item.creative_id ?? item.id}
                        {item.digest && ` · ${t("Digest")} ${item.digest}`}
                      </small>
                    </div>
                    <div className="adp-creative-decision">
                      <Status value={item.status} />
                      {portal
                        ? ["draft", "needs_changes", "rejected"].includes(
                            item.status ?? "",
                          ) && (
                            <Button
                              disabled={command.busy}
                              onClick={() =>
                                command.run(
                                  `organizations/${idPath(orgId)}/creatives/${idPath(item.id)}/actions/submit-review`,
                                  { row_version: item.row_version },
                                  t("Creative submitted for review."),
                                )
                              }
                            >
                              {t("Submit for review")}
                            </Button>
                          )
                        : can("adpilot_moderate_creatives") &&
                          ["pending", "pending_review", "submitted"].includes(
                            item.status ?? "",
                          ) && (
                            <>
                              <Button
                                kind="primary"
                                onClick={() =>
                                  setDecision({ item, action: "approve" })
                                }
                              >
                                {t("Approve version")}
                              </Button>
                              <Button
                                onClick={() =>
                                  setDecision({
                                    item,
                                    action: "request-changes",
                                  })
                                }
                              >
                                {t("Request changes")}
                              </Button>
                              <Button
                                kind="text-danger"
                                onClick={() =>
                                  setDecision({ item, action: "reject" })
                                }
                              >
                                {t("Reject")}
                              </Button>
                            </>
                          )}
                    </div>
                  </article>
                ))}
              </div>
              <Pager page={page} total={items.data.total} setPage={setPage} />
            </>
          ) : (
            <Empty
              title={
                portal
                  ? t("Your creative library starts here")
                  : t("The review queue is clear")
              }
            >
              {portal
                ? t(
                    "Create a text creative with a safe destination URL, then submit it for moderation.",
                  )
                : t(
                    "Submitted creative versions will appear here. Approval applies only to the exact reviewed version.",
                  )}
            </Empty>
          )}
        </RemoteState>
      )}
      {creating && (
        <CreativeForm
          orgId={orgId}
          onClose={() => setCreating(false)}
          onSaved={() => {
            setCreating(false);
            setRevision((value) => value + 1);
          }}
        />
      )}{" "}
      {decision && (
        <ConfirmAction
          title={
            decision.action === "approve"
              ? t("Approve creative version")
              : decision.action === "reject"
                ? t("Reject creative version")
                : t("Request changes")
          }
          description={`${decision.item.title ?? ""} · ${t("Digest")}: ${decision.item.digest ?? "—"}`}
          close={() => setDecision(null)}
          busy={command.busy}
          error={command.error}
          destructive={decision.action === "reject"}
          onConfirm={(reason: string) =>
            command.run(
              `admin/creative-reviews/${idPath(decision.item.id)}/actions/${decision.action}`,
              {
                reason,
                digest: decision.item.digest,
                row_version: decision.item.row_version,
              },
              t("Review decision recorded."),
            )
          }
        />
      )}
    </>
  );
}
function CreativeForm({ orgId, onSaved, onClose }: any) {
  const [values, setValues] = wp.element.useState({
    name: "",
    title: "",
    body: "",
    destination_url: "",
  });
  const command = useCommand(onSaved);
  const update = (key: string, value: string) =>
    setValues((previous) => ({ ...previous, [key]: value }));
  return (
    <Modal title={t("Create text creative")} onClose={onClose}>
      <form
        onSubmit={(event: any) => {
          event.preventDefault();
          command.run(
            `organizations/${idPath(orgId)}/creatives`,
            values,
            t("Creative saved as a draft."),
          );
        }}
      >
        <p>
          {t(
            "Text is escaped on output. Approval is required before delivery.",
          )}
        </p>
        <Field label={t("Internal name")}>
          <input
            required
            autoFocus
            maxLength={160}
            value={values.name}
            onChange={(event: any) => update("name", event.target.value)}
          />
        </Field>
        <Field label={t("Display title")}>
          <input
            required
            maxLength={120}
            value={values.title}
            onChange={(event: any) => update("title", event.target.value)}
          />
        </Field>
        <Field label={t("Body copy")}>
          <textarea
            required
            rows={4}
            maxLength={600}
            value={values.body}
            onChange={(event: any) => update("body", event.target.value)}
          />
        </Field>
        <Field
          label={t("Destination URL")}
          hint={t("Use an https:// website address.")}
        >
          <input
            required
            type="url"
            pattern="https://.*"
            placeholder="https://"
            value={values.destination_url}
            onChange={(event: any) =>
              update("destination_url", event.target.value)
            }
          />
        </Field>
        <Notice error={command.error} />
        <footer>
          <Button disabled={command.busy} onClick={onClose}>
            {t("Cancel")}
          </Button>
          <Button kind="primary" type="submit" disabled={command.busy}>
            {command.busy ? t("Saving…") : t("Save draft")}
          </Button>
        </footer>
      </form>
    </Modal>
  );
}

export function Marketplace({
  navigate,
  orgId,
}: {
  navigate: Navigate;
  orgId: string;
}) {
  const [page, setPage] = wp.element.useState(1),
    [search, setSearch] = wp.element.useState("");
  const [view, setView] = wp.element.useState("placements");
  const [booking, setBooking] = wp.element.useState<Entity | null>(null);
  const [orderRefresh, setOrderRefresh] = wp.element.useState(0);
  const placements = useRemote<Page>(
    listPath("catalog/placements", page, search, { per_page: "8" }),
  );
  return (
    <>
      <PageHeader
        eyebrow={t("Advertising marketplace")}
        title={t("Find your place on this site.")}
        description={t(
          "Explore published placements and choose a setting for your next campaign.",
        )}
      />
      <section className="adp-panel adp-panel-table">
        <div className="adp-marketplace-switch">
          <Button
            kind={view === "placements" ? "primary" : "secondary"}
            onClick={() => setView("placements")}
          >
            {t("Explore placements")}
          </Button>
          <Button
            kind={view === "orders" ? "primary" : "secondary"}
            onClick={() => setView("orders")}
          >
            {t("Your orders")}
          </Button>
        </div>
        {view === "orders" ? (
          <OrderList orgId={orgId} refresh={orderRefresh} />
        ) : (
          <>
            <SearchFilter
              value={search}
              onChange={(value: string) => {
                setSearch(value);
                setPage(1);
              }}
              placeholder={t("Search placements…")}
            />
            <RemoteState state={placements}>
              {placements.data?.items.length ? (
                <>
                  <div className="adp-placement-list">
                    {placements.data.items.map((item) => (
                      <article className="adp-placement" key={item.id}>
                        <div className="adp-placement-mark">
                          <Icon name="studio" size={32} />
                        </div>
                        <div>
                          <span className="adp-eyebrow">
                            {item.zone_name ?? t("Published inventory")}
                          </span>
                          <h2>{item.name ?? item.id}</h2>
                          <Status value={item.status ?? "published"} />
                          <p>
                            {t(
                              "Availability and delivery eligibility are checked by the server for your campaign schedule.",
                            )}
                          </p>
                        </div>
                        <div className="adp-placement-actions">
                          <Button
                            kind="primary"
                            disabled={!orgId}
                            title={
                              !orgId
                                ? t(
                                    "Choose an organization above to request a quote.",
                                  )
                                : undefined
                            }
                            onClick={() => setBooking(item)}
                          >
                            {t("View rates & reserve")}
                          </Button>
                          <Button
                            onClick={() => navigate("campaigns")}
                            icon="arrow"
                          >
                            {t("Create a campaign")}
                          </Button>
                        </div>
                      </article>
                    ))}
                  </div>
                  <Pager
                    perPage={8}
                    page={page}
                    total={placements.data.total}
                    setPage={setPage}
                  />
                </>
              ) : (
                <Empty title={t("No published placements available")}>
                  {t(
                    "The publisher has not made any placements available yet. Check back when inventory is published.",
                  )}
                </Empty>
              )}
            </RemoteState>
          </>
        )}
      </section>
      {booking && (
        <BookingModal
          placement={booking}
          orgId={orgId}
          onClose={() => setBooking(null)}
          onOrdered={() => setOrderRefresh((value) => value + 1)}
        />
      )}
    </>
  );
}

export function Reports({
  orgId = "",
  portal = false,
}: {
  orgId?: string;
  portal?: boolean;
}) {
  const state = useRemote<Entity | Page>(
    portal
      ? orgId
        ? `organizations/${idPath(orgId)}/reports/campaigns`
        : null
      : "admin/reports/overview",
  );
  const data = state.data as any;
  return (
    <>
      <PageHeader
        title={t("Reports")}
        description={t(
          "Delivery selections, browser render claims, and clicks are separate measures. Browser claims are untrusted and nonbillable.",
        )}
        action={<Button onClick={state.retry}>{t("Refresh report")}</Button>}
      />
      <RemoteState state={state}>
        {portal && !orgId ? (
          <Empty title={t("Choose an organization")}>
            {t("Reports are scoped to the selected organization.")}
          </Empty>
        ) : (
          <section className="adp-panel">
            <div className="adp-section-heading">
              <h2>{t("Delivery summary")}</h2>
              {data?.updated_at && (
                <span className="adp-muted">
                  {t("Updated")} {formatDate(data.updated_at)}
                </span>
              )}
            </div>
            {portal && Array.isArray(data?.items) ? (
              <div className="adp-table-scroll">
                <table className="adp-table">
                  <thead>
                    <tr>
                      <th>{t("Campaign")}</th>
                      <th>{t("Served")}</th>
                      <th>{t("Browser render claims")}</th>
                      <th>{t("Recorded clicks")}</th>
                    </tr>
                  </thead>
                  <tbody>
                    {data.items.map((item: Entity) => (
                      <tr key={item.id ?? item.campaign_id}>
                        <th scope="row">{item.name ?? item.campaign_id}</th>
                        <td>{formatNumber(item.served)}</td>
                        <td>
                          {formatNumber(item.rendered_claims ?? item.rendered)}
                        </td>
                        <td>{formatNumber(item.clicks)}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
                {!data.items.length && (
                  <Empty title={t("No campaign measurements yet")}>
                    {t(
                      "Qualified delivery activity will appear here once campaigns are eligible and serving.",
                    )}
                  </Empty>
                )}
              </div>
            ) : (
              <dl className="adp-report-list">
                {[
                  ["served", t("Served decisions")],
                  ["rendered", t("Browser render claims")],
                  ["clicks", t("Recorded clicks")],
                ].map(([key, label]) => (
                  <div key={key}>
                    <dt>{label}</dt>
                    <dd>{formatNumber(data?.[key])}</dd>
                  </div>
                ))}
                <div>
                  <dt>{t("Revenue")}</dt>
                  <dd>
                    {data?.revenue_minor !== null &&
                    typeof data?.revenue_minor === "number" &&
                    data?.currency
                      ? new Intl.NumberFormat(undefined, {
                          style: "currency",
                          currency: data.currency,
                        }).format(data.revenue_minor / 100)
                      : t("Not available")}
                  </dd>
                </div>
              </dl>
            )}
            <p className="adp-provenance">
              {data?.rendered_definition && `${data.rendered_definition} `}
              {t(
                "Counts describe different qualification stages and must not be added together. Missing financial data is not represented as zero.",
              )}
            </p>
          </section>
        )}
      </RemoteState>
      <section className="adp-future-note">
        <Icon name="reports" />
        <div>
          <strong>{t("Advanced analytics")}</strong>
          <p>
            {t(
              "Heatmaps require R2 privacy and aggregation gates. Metered billing requires R3; session replay requires R4 and separate consent. These features are not enabled here.",
            )}
          </p>
        </div>
      </section>
    </>
  );
}

export function Organization({ onCreated }: { onCreated: () => void }) {
  const state = useRemote<Page | Entity[]>("my-organizations");
  const [creating, setCreating] = wp.element.useState(false),
    [name, setName] = wp.element.useState("");
  const command = useCommand(() => {
    setCreating(false);
    setName("");
    state.retry();
    onCreated();
  });
  const orgs = Array.isArray(state.data) ? state.data : state.data?.items;
  return (
    <>
      <PageHeader
        eyebrow={t("Advertiser workspace")}
        title={t("Organization")}
        description={t(
          "A separate workspace and membership boundary for each advertiser.",
        )}
        action={
          <Button kind="primary" icon="plus" onClick={() => setCreating(true)}>
            {t("Create organization")}
          </Button>
        }
      />
      <Notice message={command.message} />
      <RemoteState state={state}>
        {orgs?.length ? (
          <section className="adp-panel">
            <div className="adp-table-scroll">
              <table className="adp-table">
                <thead>
                  <tr>
                    <th>{t("Organization")}</th>
                    <th>{t("Your role")}</th>
                    <th>{t("Status")}</th>
                  </tr>
                </thead>
                <tbody>
                  {orgs.map((item) => (
                    <tr key={item.id ?? item.organization_id}>
                      <th scope="row">
                        {item.display_name ??
                          item.name ??
                          item.organization?.display_name ??
                          item.organization?.name ??
                          item.organization_id}
                      </th>
                      <td>{item.role ?? item.membership_role ?? "—"}</td>
                      <td>
                        <Status
                          value={item.status ?? item.organization?.status}
                        />
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </section>
        ) : (
          <Empty title={t("Create your advertiser workspace")}>
            {t(
              "An organization keeps its campaigns, creatives, and reports separate from other advertisers.",
            )}
          </Empty>
        )}
      </RemoteState>
      {creating && (
        <Modal
          title={t("Create organization")}
          onClose={() => setCreating(false)}
        >
          <form
            onSubmit={(event: any) => {
              event.preventDefault();
              command.run(
                "organizations",
                { display_name: name },
                t("Organization created."),
              );
            }}
          >
            <Field label={t("Organization name")}>
              <input
                autoFocus
                required
                maxLength={160}
                value={name}
                onChange={(event: any) => setName(event.target.value)}
              />
            </Field>
            <Notice error={command.error} />
            <footer>
              <Button
                disabled={command.busy}
                onClick={() => setCreating(false)}
              >
                {t("Cancel")}
              </Button>
              <Button kind="primary" type="submit" disabled={command.busy}>
                {command.busy ? t("Creating…") : t("Create organization")}
              </Button>
            </footer>
          </form>
        </Modal>
      )}
    </>
  );
}

const settingCopy: Record<
  string,
  { label: string; hint?: string; on?: string; off?: string }
> = {
  emergency_stop: {
    label: t("Site-wide delivery stop"),
    hint: t(
      "Stops advertising delivery across this site; does not alter payments.",
    ),
    on: t("All advertising delivery is stopped"),
    off: t("Delivery is permitted when campaigns are eligible"),
  },
  manual_entitlements_enabled: {
    label: t("Manual campaign activation"),
    hint: t(
      "Requires an approved policy before complimentary reservations can be created.",
    ),
    on: t("Complimentary activation is enabled"),
    off: t("Complimentary activation is disabled"),
  },
  manual_policy_ref: {
    label: t("Manual activation policy reference"),
    hint: t(
      "Identify the approved publisher policy governing complimentary campaign reservations.",
    ),
  },
  measurement_enabled: {
    label: t("Measurement collection"),
    hint: t(
      "Requires an authoritative consent adapter, approved policy, and retention period. Collection remains gated by the production context.",
    ),
    on: t("Collection may run when consent permits"),
    off: t("Measurement collection is disabled"),
  },
  measurement_policy_ref: {
    label: t("Measurement policy reference"),
    hint: t(
      "Identify the approved privacy policy for purpose-specific event collection.",
    ),
  },
  event_retention_days: {
    label: t("Event retention (days)"),
    hint: t(
      "Maximum lifetime of collected measurement events under the approved policy.",
    ),
  },
  commerce_enabled: {
    label: t("Paid checkout"),
    hint: t(
      "Unavailable until the commerce adapter and financial certification are complete.",
    ),
    on: t("Checkout is configured"),
    off: t("Paid checkout is unavailable"),
  },
  commerce_policy_ref: {
    label: t("Commerce certification reference"),
    hint: t("Available through the dedicated commerce certification workflow."),
  },
  self_service_signup: {
    label: t("Advertiser organization signup"),
    hint: t(
      "Controls whether signed-in members may create their own advertiser organization.",
    ),
    on: t("Members may create organizations"),
    off: t("Publisher provisioning is required"),
  },
};

export function Settings() {
  const settings = useRemote<Entity>(
    can("adpilot_manage_settings") ? "admin/settings" : null,
  );
  const diagnostics = useRemote<Entity>(
    can("adpilot_view_diagnostics") ? "admin/diagnostics" : null,
  );
  const availability = useRemote<Record<string, Entity>>(
    can("adpilot_manage_settings")
      ? "admin/feature-availability"
      : "public/feature-capabilities",
  );
  const [values, setValues] = wp.element.useState<Record<string, any> | null>(
    null,
  );
  const command = useCommand(() => {
    setValues(null);
    settings.retry();
  });
  const current = values ?? settings.data?.settings ?? {};
  return (
    <>
      <PageHeader
        title={t("Settings & system health")}
        description={t(
          "Site configuration, runtime checks, and feature availability.",
        )}
      />
      {can("adpilot_manage_settings") && (
        <RemoteState state={settings}>
          <section className="adp-panel">
            <h2>{t("Site configuration")}</h2>
            <form
              onSubmit={(event: any) => {
                event.preventDefault();
                command.run(
                  "admin/settings",
                  {
                    row_version: settings.data?.row_version,
                    settings: Object.fromEntries(
                      Object.entries(current).filter(
                        ([key, value]) =>
                          value !== settings.data?.settings?.[key],
                      ),
                    ),
                  },
                  t("Settings saved."),
                  "PATCH",
                );
              }}
            >
              <div className="adp-form-grid">
                {Object.entries(current)
                  .filter(([, value]) =>
                    ["string", "number", "boolean"].includes(typeof value),
                  )
                  .map(([key, value]) => (
                    <Field
                      key={key}
                      label={
                        settingCopy[key]?.label ?? t(key.split("_").join(" "))
                      }
                      hint={
                        (key.startsWith("measurement_") ||
                          key === "event_retention_days") &&
                        !can("adpilot_manage_privacy")
                          ? t(
                              "A privacy administrator must change this setting.",
                            )
                          : settingCopy[key]?.hint
                      }
                    >
                      {typeof value === "boolean" ? (
                        <span className="adp-checkbox-label">
                          <input
                            type="checkbox"
                            disabled={
                              key.startsWith("commerce_") ||
                              ((key.startsWith("measurement_") ||
                                key === "event_retention_days") &&
                                !can("adpilot_manage_privacy"))
                            }
                            checked={value}
                            onChange={(event: any) =>
                              setValues({
                                ...current,
                                [key]: event.target.checked,
                              })
                            }
                          />
                          {value
                            ? (settingCopy[key]?.on ?? t("Enabled"))
                            : (settingCopy[key]?.off ?? t("Disabled"))}
                        </span>
                      ) : (
                        <input
                          disabled={
                            key.startsWith("commerce_") ||
                            ((key.startsWith("measurement_") ||
                              key === "event_retention_days") &&
                              !can("adpilot_manage_privacy"))
                          }
                          type={typeof value === "number" ? "number" : "text"}
                          value={String(value)}
                          maxLength={1000}
                          onChange={(event: any) =>
                            setValues({
                              ...current,
                              [key]:
                                typeof value === "number"
                                  ? Number(event.target.value)
                                  : event.target.value,
                            })
                          }
                        />
                      )}
                    </Field>
                  ))}
              </div>
              {!Object.keys(current).length && (
                <p>
                  {t("No editable site settings were returned by the server.")}
                </p>
              )}
              <Notice error={command.error} message={command.message} />
              <div className="adp-actions">
                <Button
                  type="submit"
                  kind="primary"
                  disabled={command.busy || values === null}
                >
                  {command.busy ? t("Saving…") : t("Save settings")}
                </Button>
                {values !== null && (
                  <span className="adp-muted">{t("Unsaved changes")}</span>
                )}
              </div>
            </form>
          </section>
        </RemoteState>
      )}
      {can("adpilot_view_diagnostics") && (
        <RemoteState state={diagnostics}>
          <section className="adp-panel adp-diagnostics">
            <div className="adp-section-heading">
              <h2>{t("Runtime diagnostics")}</h2>
              <Button onClick={diagnostics.retry}>
                {t("Run checks again")}
              </Button>
            </div>
            <DiagnosticData data={diagnostics.data} />
          </section>
        </RemoteState>
      )}
      <RemoteState state={availability}>
        <section className="adp-panel adp-panel-table">
          <div
            className="adp-section-heading"
            style={{ padding: "22px 22px 0" }}
          >
            <h2>{t("Feature availability")}</h2>
          </div>
          <div className="adp-table-scroll">
            <table className="adp-table">
              <thead>
                <tr>
                  <th>{t("Feature")}</th>
                  <th>{t("Availability")}</th>
                  <th>{t("Release")}</th>
                  <th>{t("Explanation")}</th>
                </tr>
              </thead>
              <tbody>
                {Object.entries(availability.data ?? {}).map(
                  ([key, feature]) => (
                    <tr key={key}>
                      <th scope="row">{t(key.split("_").join(" "))}</th>
                      <td>
                        <Status
                          value={
                            feature.available ? "available" : "unavailable"
                          }
                        />
                      </td>
                      <td>{feature.release ?? "—"}</td>
                      <td>{feature.reason ?? t("Enabled for this site")}</td>
                    </tr>
                  ),
                )}
              </tbody>
            </table>
          </div>
        </section>
      </RemoteState>
      <section className="adp-future-note">
        <Icon name="shield" />
        <div>
          <strong>{t("Release boundaries")}</strong>
          <p>
            {t(
              "Checkout adapters, advanced pricing, heatmaps, replay, and network federation require their verified module and policy gates. No external service is implied by the presence of this console.",
            )}
          </p>
        </div>
      </section>
    </>
  );
}
function DiagnosticData({ data }: { data: Entity | null }) {
  if (!data) return <p>{t("No diagnostic data returned.")}</p>;
  const labels: Record<string, string> = {
    version: t("Plugin version"),
    schema_version: t("Database schema version"),
    transactional_storage: t("Transactional storage"),
    woocommerce: t("WooCommerce integration"),
    environment: t("WordPress environment"),
    pending_outbox: t("Pending background events"),
    scheduler_next_at: t("Next maintenance run"),
  };
  const checks: Entity[] = Array.isArray(data.checks)
    ? data.checks
    : Object.entries(data)
        .filter(([key]) => !["id", "features"].includes(key))
        .map(([key, value]) => {
          let message: string;
          let status: string | undefined;
          if (key === "scheduler_next_at") {
            message =
              typeof value === "number" && value > 0
                ? formatDate(new Date(value * 1000).toISOString())
                : t("Not scheduled");
            status =
              typeof value === "number" && value > 0 ? "ready" : "pending";
          } else if (key === "pending_outbox") {
            message = `${formatNumber(value)} ${t("events waiting for processing")}`;
            status = value === 0 ? "healthy" : "pending";
          } else if (typeof value === "boolean") {
            message =
              key === "woocommerce"
                ? value
                  ? t("Installed")
                  : t("Not installed")
                : value
                  ? t("Available")
                  : t("Unavailable");
            status = value ? "healthy" : "unavailable";
          } else if (value === null || value === undefined || value === "")
            message = t("Not reported");
          else
            message =
              typeof value === "object" ? JSON.stringify(value) : String(value);
          return {
            id: key,
            name: labels[key] ?? t(key.split("_").join(" ")),
            message,
            status,
          };
        });
  return (
    <dl className="adp-diagnostic-list">
      {checks.map((item, index) => (
        <div key={item.id ?? index}>
          <dt>
            <Icon name="shield" size={17} />
            {item.name ?? item.key ?? item.label ?? t("Runtime check")}
          </dt>
          <dd>
            {item.status && <Status value={item.status} />}
            <span>
              {typeof (item.message ?? item.detail ?? item.value) === "object"
                ? JSON.stringify(item.message ?? item.detail ?? item.value)
                : String(
                    item.message ??
                      item.detail ??
                      item.value ??
                      t("Not reported"),
                  )}
            </span>
          </dd>
        </div>
      ))}
    </dl>
  );
}
