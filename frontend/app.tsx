import { can, config, Entity, Page, t, useRemote } from "./api";
import { Icon, Button } from "./ui";
import {
  Campaigns,
  Creatives,
  Inventory,
  Marketplace,
  Organization,
  Overview,
  Reports,
  Settings,
} from "./views";
import { Studio } from "./studio";

function App() {
  const portal = config?.mode === "portal";
  const [route, setRoute] = wp.element.useState(
    window.location.hash.slice(1) || (portal ? "marketplace" : "overview"),
  );
  const currentRoute = wp.element.useRef(route);
  const [orgId, setOrgId] = wp.element.useState("");
  const [orgRefresh, setOrgRefresh] = wp.element.useState(0);
  const organizations = useRemote<Page | Entity[]>(
    portal && config?.userId ? "my-organizations" : null,
    orgRefresh,
  );
  const me = useRemote<Entity>(config?.userId ? "me" : null);
  const orgItems = (
    Array.isArray(organizations.data)
      ? organizations.data
      : (organizations.data?.items ?? [])
  ).map((item) => ({
    ...item,
    id: item.organization_id ?? item.organization?.id ?? item.id,
    name:
      item.organization?.display_name ??
      item.organization?.name ??
      item.display_name ??
      item.name,
  }));
  const navItems = portal
    ? [
        {
          key: "marketplace",
          label: t("Marketplace"),
          icon: "marketplace",
          visible: true,
        },
        {
          key: "campaigns",
          label: t("Campaigns"),
          icon: "campaigns",
          visible: !!config?.userId,
        },
        {
          key: "creatives",
          label: t("Creatives"),
          icon: "creatives",
          visible: !!config?.userId,
        },
        {
          key: "reports",
          label: t("Reports"),
          icon: "reports",
          visible: !!config?.userId,
        },
        {
          key: "organization",
          label: t("Organization"),
          icon: "organization",
          visible: !!config?.userId,
        },
      ]
    : [
        {
          key: "overview",
          label: t("Overview"),
          icon: "overview",
          visible: can("adpilot_view_aggregate_analytics"),
        },
        {
          key: "inventory",
          label: t("Inventory"),
          icon: "inventory",
          visible:
            can("adpilot_view_inventory") || can("adpilot_manage_inventory"),
        },
        {
          key: "studio",
          label: t("Layout Studio"),
          icon: "studio",
          visible:
            can("adpilot_view_inventory") || can("adpilot_manage_inventory"),
        },
        {
          key: "campaigns",
          label: t("Campaigns"),
          icon: "campaigns",
          visible: can("adpilot_view_campaigns"),
        },
        {
          key: "creatives",
          label: t("Creatives"),
          icon: "creatives",
          visible: can("adpilot_moderate_creatives"),
        },
        {
          key: "reports",
          label: t("Reports"),
          icon: "reports",
          visible: can("adpilot_view_aggregate_analytics"),
        },
        {
          key: "settings",
          label: t("Settings"),
          icon: "settings",
          visible:
            can("adpilot_manage_settings") || can("adpilot_view_diagnostics"),
        },
      ];
  const permittedNav = navItems.filter((item) => item.visible);
  function allowedToLeave() {
    return window.dispatchEvent(
      new Event("adpilot:before-route", { cancelable: true }),
    );
  }
  function navigate(next: string) {
    window.location.hash = next;
  }
  wp.element.useEffect(() => {
    const handleHash = () => {
      const next = window.location.hash.slice(1);
      if (next !== currentRoute.current && !allowedToLeave()) {
        window.history.replaceState(null, "", `#${currentRoute.current}`);
        return;
      }
      setRoute(next || (portal ? "marketplace" : "overview"));
    };
    window.addEventListener("hashchange", handleHash);
    return () => window.removeEventListener("hashchange", handleHash);
  }, [portal]);
  wp.element.useEffect(() => {
    currentRoute.current = route;
    if (
      !window.location.hash &&
      permittedNav.length &&
      !permittedNav.some((item) => item.key === route)
    )
      setRoute(permittedNav[0].key);
    document.getElementById("adp-page-title")?.focus({ preventScroll: true });
  }, [route]);
  const permitted = permittedNav.some((item) => item.key === route);
  let content;
  if (!config)
    content = (
      <section className="adp-empty" role="alert">
        <h1>{t("Open AdPilot from WordPress")}</h1>
        <p>
          {t(
            "The application needs the site REST URL, user capabilities, and a session nonce supplied by WordPress.",
          )}
        </p>
      </section>
    );
  else if (!permitted)
    content = (
      <section className="adp-empty">
        <h1 id="adp-page-title" tabIndex={-1}>
          {t("This view is not available")}
        </h1>
        <p>{t("Your current permissions do not allow this view.")}</p>
        {permittedNav[0] && (
          <Button onClick={() => navigate(permittedNav[0].key)}>
            {t("Go to your workspace")}
          </Button>
        )}
      </section>
    );
  else if (route === "overview") content = <Overview navigate={navigate} />;
  else if (route === "inventory") content = <Inventory />;
  else if (route === "studio") content = <Studio />;
  else if (route === "campaigns")
    content = <Campaigns key={orgId} orgId={orgId} portal={portal} />;
  else if (route === "creatives")
    content = <Creatives key={orgId} orgId={orgId} portal={portal} />;
  else if (route === "reports")
    content = <Reports key={orgId} orgId={orgId} portal={portal} />;
  else if (route === "settings") content = <Settings />;
  else if (route === "marketplace")
    content = <Marketplace navigate={navigate} orgId={orgId} />;
  else
    content = (
      <Organization onCreated={() => setOrgRefresh((value) => value + 1)} />
    );
  const initial = (me.data?.display_name ?? me.data?.name ?? "A").slice(0, 1);
  return (
    <div className={`adp-app ${portal ? "adp-portal" : "adp-admin"}`}>
      <a href="#adp-main" className="adp-skip-link" onClick={(event: any) => { event.preventDefault(); document.getElementById("adp-main")?.focus(); }}>
        {t("Skip to main content")}
      </a>
      <aside className="adp-sidebar">
        <a
          href={`#${portal ? "marketplace" : (permittedNav[0]?.key ?? "overview")}`}
          className="adp-brand"
          aria-label="AdPilot"
        >
          <span className="adp-brand-symbol">
            <svg
              viewBox="0 0 32 32"
              width="29"
              height="29"
              fill="none"
              aria-hidden="true"
            >
              <path
                d="M4 25L15.5 5 28 25H4Z"
                stroke="currentColor"
                strokeWidth="3"
              />
              <path
                d="M12 19h10M16 12v13"
                stroke="currentColor"
                strokeWidth="3"
              />
            </svg>
          </span>
          <strong>
            AdPilot<span> /</span>
          </strong>
        </a>
        <div className="adp-sidebar-context">
          <span className="adp-site-dot" />
          <span>
            {portal ? t("Advertiser workspace") : t("Publisher console")}
          </span>
        </div>
        <nav aria-label={t("AdPilot navigation")}>
          <span className="adp-nav-section-label">{t("Workspace")}</span>
          {permittedNav.map((item) => (
            <a
              key={item.key}
              href={`#${item.key}`}
              className={`adp-nav-link ${route === item.key ? "is-current" : ""}`}
              aria-label={item.label}
              aria-current={route === item.key ? "page" : undefined}
              onClick={(event: any) => {
                event.preventDefault();
                navigate(item.key);
              }}
            >
              <Icon name={item.icon} />
              <span>{item.label}</span>
              {route === item.key && <span className="adp-nav-current" />}
            </a>
          ))}
        </nav>
        <div className="adp-sidebar-bottom">
          <span className="adp-version-tag">R1</span>
          <strong>{t("Built for your site.")}</strong>
          <p>
            {t("Inventory, creative, and delivery — with clear boundaries.")}
          </p>
          <div className="adp-site-scope">
            <Icon name="shield" size={14} />
            {t("Site scope")} {config?.siteId ?? "—"}
          </div>
        </div>
      </aside>
      <div className="adp-main-shell">
        <header className="adp-topbar">
          <div className="adp-breadcrumb">
            {portal ? t("My advertising") : t("AdPilot")}
            <span>/</span>
            <strong>
              {permittedNav.find((item) => item.key === route)?.label ??
                t("Workspace")}
            </strong>
          </div>
          <div className="adp-topbar-end">
            {portal && !!config?.userId && (
              <label className="adp-org-select">
                <span className="adp-sr-only">{t("Active organization")}</span>
                <select
                  value={orgId}
                  onChange={(event: any) => {
                    if (allowedToLeave()) setOrgId(event.target.value);
                  }}
                >
                  <option value="">
                    {organizations.loading
                      ? t("Loading organizations…")
                      : t("Choose organization")}
                  </option>
                  {orgItems.map((item) => (
                    <option key={item.id} value={item.id}>
                      {item.name}
                    </option>
                  ))}
                </select>
              </label>
            )}
            <span className="adp-account-avatar" aria-hidden="true">
              {initial}
            </span>
            <span className="adp-account-name">
              {me.data?.display_name ??
                me.data?.name ??
                (config?.userId ? t("Site member") : t("Guest"))}
            </span>
          </div>
        </header>
        {portal && organizations.error && (
          <div className="adp-notice adp-notice-error" role="alert">
            {organizations.error.message}
            <Button onClick={organizations.retry}>
              {t("Retry organizations")}
            </Button>
          </div>
        )}
        <main
          id="adp-main"
          tabIndex={-1}
          className="adp-main"
          key={`${route}-${portal ? orgId : ""}`}
        >
          {content}
        </main>
        <footer className="adp-app-footer">
          <span>
            AdPilot <span>·</span> {t("Advertising operations, clearly.")}
          </span>
          <span>{t("All actions are subject to server authorization.")}</span>
        </footer>
      </div>
    </div>
  );
}
const root = document.getElementById("adpilot-app");
if (root) {
  if (wp.element.createRoot) wp.element.createRoot(root).render(<App />);
  else wp.element.render?.(<App />, root);
}
