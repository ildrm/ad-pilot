import {
  can,
  Entity,
  formatNumber,
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
  Icon,
  Modal,
  Notice,
  PageHeader,
  Pager,
  RemoteState,
  Status,
} from "./ui";
import { InventoryForm } from "./views";

interface Definition {
  name?: string;
  zones: { zone_id: string; container_ids: string[] }[];
}
export function Studio() {
  const [revision, setRevision] = wp.element.useState(0);
  const zones = useRemote<Page>("admin/zones?per_page=100", revision);
  const containers = useRemote<Page>("admin/containers?per_page=100", revision);
  const layouts = useRemote<Page>("admin/layouts?per_page=100", revision);
  const [selected, setSelected] = wp.element.useState("");
  const [draft, setDraft] = wp.element.useState<Entity | null>(null);
  const [definition, setDefinition] = wp.element.useState<Definition>({
    zones: [],
  });
  const [history, setHistory] = wp.element.useState<Definition[]>([]);
  const [future, setFuture] = wp.element.useState<Definition[]>([]);
  const [dirty, setDirty] = wp.element.useState(false);
  const [dialog, setDialog] = wp.element.useState<string | null>(null);
  const [viewport, setViewport] = wp.element.useState("desktop");
  const [view, setView] = wp.element.useState("canvas");
  const [validation, setValidation] = wp.element.useState<Entity | null>(null);
  const [name, setName] = wp.element.useState("");
  const [draftZoneIds, setDraftZoneIds] = wp.element.useState<string[] | null>(
    null,
  );
  const [slotPage, setSlotPage] = wp.element.useState(1);
  const [busy, setBusy] = wp.element.useState(false);
  const [error, setError] = wp.element.useState<Error | null>(null);
  const [message, setMessage] = wp.element.useState("");
  const saving = wp.element.useRef(false);
  const allContainers = containers.data?.items ?? [];
  const allZones = zones.data?.items ?? [];
  const chosen = allContainers.find((item) => item.id === selected);
  const chosenZone = allZones.find(
    (item) => item.id === (chosen?.zone_id ?? selected),
  );
  const slotState = useRemote<Page>(
    view === "slots" && chosen
      ? listPath("admin/slots", slotPage, "", { container_id: chosen.id })
      : null,
    revision,
  );
  const activeZoneIds = draft
    ? definition.zones.map((item) => item.zone_id)
    : allZones.map((item) => item.id);
  const visibleContainers = allContainers.filter(
    (container) =>
      activeZoneIds.includes(container.zone_id) &&
      (!draft ||
        definition.zones
          .find((zone) => zone.zone_id === container.zone_id)
          ?.container_ids.includes(container.id)),
  );
  const totalSlots = visibleContainers.reduce(
    (total, item) => total + Number(item.slot_count ?? 0),
    0,
  );
  const draftZones = allZones.filter((zone) =>
    (draftZoneIds ?? allZones.map((item) => item.id)).includes(zone.id),
  );
  const draftContainers = allContainers.filter((container) =>
    draftZones.some((zone) => zone.id === container.zone_id),
  );
  const previewQuota = Math.max(
    1,
    Math.floor(120 / Math.max(visibleContainers.length, 1)),
  );
  const canEdit = can("adpilot_manage_inventory");

  async function operate(
    path: string,
    body: unknown,
    success: string,
    method = "POST",
  ) {
    if (saving.current) return null;
    saving.current = true;
    setBusy(true);
    setError(null);
    setMessage("");
    try {
      const result = await request<Entity>(path, { method, body });
      setMessage(success);
      return result;
    } catch (failure) {
      setError(failure as Error);
      return null;
    } finally {
      saving.current = false;
      setBusy(false);
    }
  }
  async function save() {
    if (!draft || !dirty) return;
    const result = await operate(
      `admin/layouts/drafts/${idPath(draft.id)}`,
      { row_version: draft.row_version, definition },
      t("Draft saved. Validate the saved version before publishing."),
      "PATCH",
    );
    if (result) {
      setDraft(result);
      setDirty(false);
      setValidation(null);
      setRevision((value) => value + 1);
    }
  }
  wp.element.useEffect(() => {
    const unload = (event: BeforeUnloadEvent) => {
      if (dirty) {
        event.preventDefault();
        event.returnValue = "";
      }
    };
    const leave = (event: Event) => {
      if (
        dirty &&
        !window.confirm(
          t("Discard unsaved layout changes and leave Layout Studio?"),
        )
      )
        event.preventDefault();
    };
    window.addEventListener("beforeunload", unload);
    window.addEventListener("adpilot:before-route", leave);
    return () => {
      window.removeEventListener("beforeunload", unload);
      window.removeEventListener("adpilot:before-route", leave);
    };
  }, [dirty]);
  wp.element.useEffect(() => {
    const shortcut = (event: KeyboardEvent) => {
      if ((event.metaKey || event.ctrlKey) && event.key === "s") {
        event.preventDefault();
        save();
      }
    };
    window.addEventListener("keydown", shortcut);
    return () => window.removeEventListener("keydown", shortcut);
  }, [draft, definition, dirty]);
  function edit(next: Definition) {
    setHistory((previous) => [...previous.slice(-19), definition]);
    setFuture([]);
    setDefinition(next);
    setDirty(true);
    setValidation(null);
  }
  function undo() {
    if (!history.length) return;
    setFuture((previous) => [definition, ...previous]);
    setDefinition(history[history.length - 1]);
    setHistory((previous) => previous.slice(0, -1));
    setDirty(true);
    setValidation(null);
  }
  function redo() {
    if (!future.length) return;
    setHistory((previous) => [...previous, definition]);
    setDefinition(future[0]);
    setFuture((previous) => previous.slice(1));
    setDirty(true);
    setValidation(null);
  }
  function selectDraft(item: Entity | null) {
    if (
      dirty &&
      !window.confirm(t("Discard unsaved layout changes and switch views?"))
    )
      return;
    setDraft(item);
    setDefinition(item?.definition ?? { zones: [] });
    setDirty(false);
    setHistory([]);
    setFuture([]);
    setValidation(null);
    setError(null);
    setMessage("");
  }
  function moveZone(zoneId: string, offset: number) {
    const index = definition.zones.findIndex((zone) => zone.zone_id === zoneId);
    const next = [...definition.zones];
    const target = index + offset;
    if (target < 0 || target >= next.length) return;
    [next[index], next[target]] = [next[target], next[index]];
    edit({ ...definition, zones: next });
  }
  const displayedZones = draft
    ? (definition.zones
        .map((ref) => allZones.find((item) => item.id === ref.zone_id))
        .filter(Boolean) as Entity[])
    : allZones;
  return (
    <div className="adp-studio-page">
      <PageHeader
        title={t("Layout Studio")}
        description={t(
          "Arrange your inventory once. Keep every logical slot identity intact.",
        )}
        action={
          <>
            <Button
              disabled={!draft || !dirty || busy || !canEdit}
              onClick={save}
            >
              {busy ? t("Working…") : t("Save draft")}
            </Button>
            <Button
              disabled={
                !draft ||
                dirty ||
                busy ||
                !canEdit ||
                draft.status === "published"
              }
              onClick={async () => {
                if (!draft) return;
                const result = await operate(
                  `admin/layouts/drafts/${idPath(draft.id)}/actions/validate`,
                  { row_version: draft.row_version },
                  t("Server validation completed. Review the result below."),
                );
                if (result) setValidation(result);
              }}
            >
              {t("Validate")}
            </Button>
            <Button
              kind="primary"
              disabled={
                !draft ||
                dirty ||
                busy ||
                !validation?.valid ||
                !can("adpilot_publish_layout") ||
                draft.status === "published"
              }
              onClick={() => setDialog("publish")}
            >
              {t("Publish layout")}
            </Button>
          </>
        }
      />
      <Notice error={error} message={message} />
      {validation && (
        <div
          className={`adp-notice ${validation.valid ? "adp-notice-success" : "adp-notice-error"}`}
          role="status"
        >
          <strong>
            {validation.valid
              ? t("Saved draft is valid")
              : t("Validation needs attention")}
          </strong>
          {Array.isArray(validation.errors) &&
            validation.errors.map((issue: any, index: number) => (
              <span key={index}>
                {typeof issue === "string"
                  ? issue
                  : (issue.message ?? issue.code)}
              </span>
            ))}
          {Array.isArray(validation.warnings) &&
            validation.warnings.map((issue: any, index: number) => (
              <span key={index}>
                {typeof issue === "string"
                  ? issue
                  : (issue.message ?? issue.code)}
              </span>
            ))}
        </div>
      )}
      <div className="adp-studio-toolbar">
        <div className="adp-studio-title">
          <Icon name="studio" />
          <label className="adp-sr-only" htmlFor="adp-layout-selector">
            {t("Layout workspace")}
          </label>
          <select
            id="adp-layout-selector"
            value={draft?.id ?? ""}
            onChange={(event: any) =>
              selectDraft(
                layouts.data?.items.find(
                  (item) => item.id === event.target.value,
                ) ?? null,
              )
            }
          >
            <option value="">{t("Inventory preview")}</option>
            {layouts.data?.items.map((item) => (
              <option key={item.id} value={item.id}>
                {item.name} · {item.status}
              </option>
            ))}
          </select>
          <Status value={dirty ? "unsaved" : (draft?.status ?? "preview")} />
        </div>
        <div className="adp-toolbar-actions">
          <span className="adp-muted">
            {formatNumber(totalSlots)} {t("logical slots")}
          </span>
          {canEdit && (
            <Button
              icon="plus"
              onClick={() => {
                setDraftZoneIds(allZones.map((item) => item.id));
                setDialog("draft");
              }}
            >
              {t("New draft")}
            </Button>
          )}
          <Button disabled={!history.length || busy} onClick={undo}>
            {t("Undo")}
          </Button>
          <Button disabled={!future.length || busy} onClick={redo}>
            {t("Redo")}
          </Button>
        </div>
      </div>
      <RemoteState state={zones}>
        <RemoteState state={containers}>
          {!allZones.length ? (
            <section className="adp-panel">
              <Empty
                title={t("Start with a zone")}
                action={
                  canEdit && (
                    <Button
                      kind="primary"
                      icon="plus"
                      onClick={() => setDialog("zone")}
                    >
                      {t("Create first zone")}
                    </Button>
                  )
                }
              >
                {t(
                  "Create a zone, add a container with your requested slot count, then save and publish a layout draft.",
                )}
              </Empty>
            </section>
          ) : (
            <div className="adp-studio-workspace">
              <aside
                className="adp-layout-tree"
                aria-label={t("Zones and containers")}
              >
                <div className="adp-pane-title">
                  <h2>{t("Structure")}</h2>
                  {canEdit && (
                    <button
                      type="button"
                      className="adp-icon-button"
                      aria-label={t("Create zone")}
                      onClick={() => setDialog("zone")}
                    >
                      <Icon name="plus" size={16} />
                    </button>
                  )}
                </div>
                <div className="adp-tree-root">
                  <Icon name="studio" size={16} />
                  {t("Page layout")}
                  <span>{displayedZones.length}</span>
                </div>
                {displayedZones.map((zone, index) => (
                  <section className="adp-tree-zone" key={zone.id}>
                    <button
                      className={`adp-tree-label ${selected === zone.id ? "is-selected" : ""}`}
                      type="button"
                      onClick={() => {
                        setSelected(zone.id);
                        setSlotPage(1);
                      }}
                      aria-pressed={selected === zone.id}
                    >
                      <span className="adp-tree-chevron">⌄</span>
                      <Icon name="inventory" size={15} />
                      <strong>{zone.name}</strong>
                    </button>
                    {allContainers
                      .filter((item) => item.zone_id === zone.id)
                      .map((container) => (
                        <button
                          type="button"
                          key={container.id}
                          className={`adp-tree-container ${selected === container.id ? "is-selected" : ""}`}
                          aria-pressed={selected === container.id}
                          onClick={() => {
                            setSelected(container.id);
                            setSlotPage(1);
                          }}
                        >
                          <span className="adp-container-symbol" />
                          <span>{container.name}</span>
                          <small>{formatNumber(container.slot_count)}</small>
                        </button>
                      ))}
                    {draft && canEdit && (
                      <div className="adp-tree-order">
                        <button
                          type="button"
                          disabled={index === 0}
                          aria-label={`${t("Move zone up")}: ${zone.name}`}
                          onClick={() => moveZone(zone.id, -1)}
                        >
                          ↑
                        </button>
                        <button
                          type="button"
                          disabled={index === displayedZones.length - 1}
                          aria-label={`${t("Move zone down")}: ${zone.name}`}
                          onClick={() => moveZone(zone.id, 1)}
                        >
                          ↓
                        </button>
                      </div>
                    )}
                  </section>
                ))}
                {canEdit && (
                  <Button onClick={() => setDialog("container")} icon="plus">
                    {t("Add container")}
                  </Button>
                )}
                <div className="adp-tree-footer">
                  <Icon name="shield" size={16} />
                  <span>
                    {t("Stable identities. No ads served in preview.")}
                  </span>
                </div>
              </aside>
              <section
                className="adp-layout-preview"
                aria-label={t("Layout preview")}
              >
                <div className="adp-preview-toolbar">
                  <div
                    className="adp-segmented"
                    role="group"
                    aria-label={t("Preview mode")}
                  >
                    <button
                      type="button"
                      aria-pressed={view === "canvas"}
                      onClick={() => setView("canvas")}
                    >
                      {t("Canvas")}
                    </button>
                    <button
                      type="button"
                      aria-pressed={view === "slots"}
                      onClick={() => setView("slots")}
                    >
                      {t("Slot navigator")}
                    </button>
                  </div>
                  {view === "canvas" && (
                    <div
                      className="adp-segmented"
                      role="group"
                      aria-label={t("Preview width")}
                    >
                      {["desktop", "tablet", "mobile"].map((key) => (
                        <button
                          type="button"
                          key={key}
                          aria-pressed={viewport === key}
                          onClick={() => setViewport(key)}
                        >
                          {t(key[0].toUpperCase() + key.slice(1))}
                        </button>
                      ))}
                    </div>
                  )}
                </div>
                {view === "canvas" ? (
                  <>
                    <div className="adp-canvas-scroll">
                      <div
                        className={`adp-page-canvas adp-viewport-${viewport}`}
                      >
                        <div className="adp-canvas-site">
                          <span className="adp-canvas-logo" />
                          <strong>{t("Your site")}</strong>
                          <span className="adp-canvas-nav" />
                          <span className="adp-canvas-nav" />
                          <span className="adp-canvas-nav" />
                        </div>
                        <div className="adp-canvas-regions">
                          {visibleContainers.map(
                            (container, containerIndex) => {
                              const region = /sidebar/i.test(
                                container.name ?? "",
                              )
                                ? "sidebar"
                                : /content/i.test(container.name ?? "")
                                  ? "content"
                                  : "wide";
                              const sample = Math.min(
                                Number(container.slot_count),
                                region === "sidebar"
                                  ? Math.min(8, previewQuota)
                                  : previewQuota,
                                Math.max(
                                  0,
                                  120 - containerIndex * previewQuota,
                                ),
                              );
                              return (
                                <button
                                  type="button"
                                  key={container.id}
                                  className={`adp-canvas-container adp-region-${region} ${selected === container.id ? "is-selected" : ""}`}
                                  onClick={() => {
                                    setSelected(container.id);
                                    setSlotPage(1);
                                  }}
                                  aria-pressed={selected === container.id}
                                  aria-label={`${container.name}: ${formatNumber(container.slot_count)} ${t("configured logical slots. Select to inspect.")}`}
                                >
                                  <span className="adp-canvas-container-heading">
                                    <strong>{container.name}</strong>
                                    <span>
                                      {formatNumber(container.slot_count)}{" "}
                                      {t("slots")}
                                    </span>
                                  </span>
                                  <span
                                    className="adp-preview-cells"
                                    style={{
                                      gridTemplateColumns: `repeat(${Math.min(Number(container.columns) || 5, viewport === "mobile" ? 3 : 10)}, minmax(0, 1fr))`,
                                      gap: `${Math.min(Number(container.gap) || 4, 5)}px`,
                                    }}
                                  >
                                    {Array.from(
                                      { length: sample },
                                      (_, index) => (
                                        <span
                                          className="adp-preview-cell"
                                          key={index}
                                          aria-hidden="true"
                                          style={{
                                            blockSize:
                                              region === "sidebar"
                                                ? "10px"
                                                : "14px",
                                          }}
                                        />
                                      ),
                                    )}
                                  </span>
                                  <span className="adp-canvas-container-footer">
                                    {sample < container.slot_count
                                      ? `${sample} ${t("representative cells")} · ${t("bounded preview")}`
                                      : t("Schematic preview · no impressions")}
                                  </span>
                                </button>
                              );
                            },
                          )}
                          {!visibleContainers.length && (
                            <Empty
                              title={t("Add a container to see your layout")}
                            >
                              {t(
                                "Select a zone and create a container with a logical slot count.",
                              )}
                            </Empty>
                          )}
                        </div>
                        <div className="adp-canvas-site-footer">
                          {t("End of page")}
                          <span>AdPilot / {t("synthetic preview")}</span>
                        </div>
                      </div>
                    </div>
                    <div className="adp-canvas-caption">
                      <span>
                        <i />
                        {t("Configured inventory")}
                      </span>
                      <span>
                        {t(
                          "Maximum 120 representative cells. Real identities are in Slot navigator.",
                        )}
                      </span>
                    </div>
                  </>
                ) : !chosen ? (
                  <Empty title={t("Select a container")}>
                    {t(
                      "Choose a container in the structure tree to inspect its persisted slot identities.",
                    )}
                  </Empty>
                ) : (
                  <RemoteState state={slotState}>
                    <div className="adp-table-scroll">
                      <table className="adp-table">
                        <thead>
                          <tr>
                            <th>{t("Slot identity")}</th>
                            <th>{t("Ordinal")}</th>
                            <th>{t("Status")}</th>
                          </tr>
                        </thead>
                        <tbody>
                          {slotState.data?.items.map((item) => (
                            <tr key={item.id}>
                              <th scope="row">
                                <code>{item.id}</code>
                              </th>
                              <td>{item.ordinal}</td>
                              <td>
                                <Status value={item.status} />
                              </td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                    <Pager
                      page={slotPage}
                      total={slotState.data?.total ?? 0}
                      setPage={setSlotPage}
                    />
                  </RemoteState>
                )}
              </section>
              <aside
                className="adp-layout-inspector"
                aria-label={t("Selection inspector")}
              >
                <div className="adp-pane-title">
                  <h2>{t("Inspector")}</h2>
                  <Icon name="settings" size={16} />
                </div>
                {chosen ? (
                  <ContainerInspector
                    key={`${chosen.id}-${chosen.row_version}`}
                    container={chosen}
                    onSaved={() => {
                      setRevision((value) => value + 1);
                      setValidation(null);
                    }}
                    canEdit={canEdit && chosen.status !== "published"}
                  />
                ) : chosenZone ? (
                  <div className="adp-inspector-content">
                    <div className="adp-eyebrow">{t("Zone")}</div>
                    <h3>{chosenZone.name}</h3>
                    <Status value={chosenZone.status} />
                    <p>
                      {chosenZone.description || t("Reusable inventory zone.")}
                    </p>
                    <Field label={t("Stable identity")}>
                      <code className="adp-identity">{chosenZone.id}</code>
                    </Field>
                    {canEdit && (
                      <Button
                        onClick={() => setDialog("container")}
                        icon="plus"
                      >
                        {t("Add container")}
                      </Button>
                    )}
                  </div>
                ) : (
                  <div className="adp-inspector-content">
                    <span className="adp-inspector-empty">
                      <Icon name="studio" size={30} />
                    </span>
                    <h3>{t("Every slot has a place.")}</h3>
                    <p>
                      {t(
                        "Select a container on the canvas or in the tree to inspect its count, grid, and persistent identity.",
                      )}
                    </p>
                    <dl className="adp-inspector-counts">
                      <div>
                        <dt>{t("Zones")}</dt>
                        <dd>{displayedZones.length}</dd>
                      </div>
                      <div>
                        <dt>{t("Containers")}</dt>
                        <dd>{visibleContainers.length}</dd>
                      </div>
                      <div>
                        <dt>{t("Logical slots")}</dt>
                        <dd>{formatNumber(totalSlots)}</dd>
                      </div>
                    </dl>
                  </div>
                )}
                <div className="adp-inspector-note">
                  <Icon name="shield" size={16} />
                  <p>
                    {t(
                      "Preview is synthetic and nonbillable. Publishing requires server validation and current authorization.",
                    )}
                  </p>
                </div>
              </aside>
            </div>
          )}
        </RemoteState>
      </RemoteState>
      <p className="adp-provenance">
        {dirty
          ? t("Unsaved layout changes. Save before validation.")
          : draft?.status === "published"
            ? t("Published immutable layout. Create a new draft to change its references.")
          : draft
            ? `${t("Draft version")} ${draft.row_version ?? "—"}. ${t("Save → Validate → Publish.")}`
            : t(
                "Inventory preview. Create or open a draft to validate and publish.",
              )}{" "}
        {t(
          "Commercial capacity and observed delivery are evaluated separately.",
        )}
      </p>
      {(dialog === "zone" || dialog === "container") && (
        <InventoryForm
          type={dialog}
          zoneId={chosen?.zone_id ?? chosenZone?.id ?? ""}
          onClose={() => setDialog(null)}
          onSaved={(item: Entity) => {
            setRevision((value) => value + 1);
            setDialog(null);
            setSelected(item.id);
            if (draft && item.zone_id) {
              const refs = definition.zones.map((zone) =>
                zone.zone_id === item.zone_id
                  ? { ...zone, container_ids: [...zone.container_ids, item.id] }
                  : zone,
              );
              edit({ ...definition, zones: refs });
            }
          }}
        />
      )}
      {dialog === "draft" && (
        <Modal title={t("Create layout draft")} onClose={() => setDialog(null)}>
          <form
            onSubmit={async (event: any) => {
              event.preventDefault();
              const refs: Definition = {
                name,
                zones: draftZones.map((zone) => ({
                  zone_id: zone.id,
                  container_ids: allContainers
                    .filter((item) => item.zone_id === zone.id)
                    .map((item) => item.id),
                })),
              };
              const result = await operate(
                "admin/layouts/drafts",
                { name, zones: refs.zones },
                t("Layout draft created."),
              );
              if (result) {
                setDraft(result);
                setDefinition(result.definition ?? refs);
                setDirty(false);
                setHistory([]);
                setFuture([]);
                setValidation(null);
                setDialog(null);
                setName("");
                setRevision((value) => value + 1);
              }
            }}
          >
            <p>
              {t(
                "The draft references the existing inventory. Publishing creates an immutable revision without replacing stable slot identities.",
              )}
            </p>
            <Field label={t("Layout name")}>
              <input
                required
                autoFocus
                maxLength={160}
                value={name}
                onChange={(event: any) => setName(event.target.value)}
                placeholder={t("e.g. Homepage layout")}
              />
            </Field>
            <fieldset className="adp-draft-zone-picker">
              <legend>{t("Include zones")}</legend>
              {allZones.map((zone) => (
                <label className="adp-checkbox-label" key={zone.id}>
                  <input
                    type="checkbox"
                    checked={draftZones.some((item) => item.id === zone.id)}
                    onChange={(event: any) =>
                      setDraftZoneIds((previous) =>
                        event.target.checked
                          ? [
                              ...(previous ?? allZones.map((item) => item.id)),
                              zone.id,
                            ]
                          : (
                              previous ?? allZones.map((item) => item.id)
                            ).filter((id) => id !== zone.id),
                      )
                    }
                  />
                  <span>{zone.name}</span>
                </label>
              ))}
            </fieldset>
            <div className="adp-form-summary">
              {draftZones.length} {t("zones")} · {draftContainers.length}{" "}
              {t("containers")} ·{" "}
              {formatNumber(
                draftContainers.reduce(
                  (total, item) => total + Number(item.slot_count ?? 0),
                  0,
                ),
              )}{" "}
              {t("logical slots")}
            </div>
            <Notice error={error} />
            <footer>
              <Button disabled={busy} onClick={() => setDialog(null)}>
                {t("Cancel")}
              </Button>
              <Button
                type="submit"
                kind="primary"
                disabled={busy || dirty || !draftContainers.length}
              >
                {busy ? t("Creating…") : t("Create draft")}
              </Button>
            </footer>
            {dirty && (
              <p>{t("Save your current draft before creating another.")}</p>
            )}
          </form>
        </Modal>
      )}
      {dialog === "publish" && draft && (
        <Modal
          title={t("Publish validated layout")}
          onClose={() => setDialog(null)}
        >
          <p>
            {t(
              "This creates a published immutable revision. The server will recheck the saved draft version, inventory, and commercial impact before committing.",
            )}
          </p>
          <dl className="adp-report-list">
            <div>
              <dt>{t("Layout")}</dt>
              <dd>{draft.name}</dd>
            </div>
            <div>
              <dt>{t("Draft version")}</dt>
              <dd>{draft.row_version}</dd>
            </div>
            <div>
              <dt>{t("Configured slots")}</dt>
              <dd>{formatNumber(totalSlots)}</dd>
            </div>
          </dl>
          <Notice error={error} />
          <footer>
            <Button disabled={busy} onClick={() => setDialog(null)}>
              {t("Cancel")}
            </Button>
            <Button
              kind="primary"
              disabled={busy}
              onClick={async () => {
                const result = await operate(
                  `admin/layouts/drafts/${idPath(draft.id)}/actions/publish`,
                  { row_version: draft.row_version },
                  t("Layout published. Stable inventory identities preserved."),
                );
                if (result) {
                  setDraft({ ...draft, status: "published", published_revision_id: result.id, published_revision: result.revision });
                  setValidation(null);
                  setDialog(null);
                  setRevision((value) => value + 1);
                }
              }}
            >
              {busy ? t("Publishing…") : t("Publish layout")}
            </Button>
          </footer>
        </Modal>
      )}
    </div>
  );
}

function ContainerInspector({
  container,
  onSaved,
  canEdit,
}: {
  container: Entity;
  onSaved: () => void;
  canEdit: boolean;
}) {
  const [values, setValues] = wp.element.useState({
    columns: container.columns ?? 5,
    gap: container.gap ?? 8,
    aspect_ratio: container.aspect_ratio ?? "4/1",
  });
  const [dirty, setDirty] = wp.element.useState(false);
  const command = useCommand(() => {
    setDirty(false);
    onSaved();
  });
  const update = (key: string, value: any) => {
    setValues((previous) => ({ ...previous, [key]: value }));
    setDirty(true);
  };
  return (
    <form
      className="adp-inspector-content"
      onSubmit={(event: any) => {
        event.preventDefault();
        command.run(
          `admin/containers/${idPath(container.id)}`,
          {
            ...values,
            rows: Math.ceil(container.slot_count / Math.max(values.columns, 1)),
            row_version: container.row_version,
          },
          t("Container presentation saved."),
          "PATCH",
        );
      }}
    >
      <div className="adp-eyebrow">{t("Slot container")}</div>
      <h3>{container.name}</h3>
      <Status value={container.status} />
      <div className="adp-inspector-total">
        <strong>{formatNumber(container.slot_count)}</strong>
        <span>{t("configured logical slots")}</span>
      </div>
      <h4>{t("Presentation")}</h4>
      <Field label={t("Columns")}>
        <input
          type="number"
          required
          min={1}
          max={20}
          disabled={!canEdit}
          value={values.columns}
          onChange={(event: any) =>
            update("columns", Number(event.target.value))
          }
        />
      </Field>
      <Field label={t("Gap (px)")}>
        <input
          type="number"
          required
          min={0}
          max={64}
          disabled={!canEdit}
          value={values.gap}
          onChange={(event: any) => update("gap", Number(event.target.value))}
        />
      </Field>
      <Field label={t("Cell aspect ratio")}>
        <select
          value={values.aspect_ratio}
          disabled={!canEdit}
          onChange={(event: any) => update("aspect_ratio", event.target.value)}
        >
          <option value="4/1">4:1 · {t("Banner")}</option>
          <option value="3/2">3:2 · {t("Landscape")}</option>
          <option value="1/1">1:1 · {t("Square")}</option>
        </select>
      </Field>
      <Field label={t("Stable identity")}>
        <code className="adp-identity">{container.id}</code>
      </Field>
      <Notice error={command.error} message={command.message} />
      {canEdit && (
        <Button kind="primary" type="submit" disabled={!dirty || command.busy}>
          {command.busy ? t("Saving…") : t("Save presentation")}
        </Button>
      )}
      <p className="adp-small">
        {t(
          "Slot counts and identities are not changed by presentation edits. The server protects published and commercially committed inventory.",
        )}
      </p>
    </form>
  );
}
