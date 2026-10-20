import { Page, request, t, useRemote } from "./api";

const {
  PanelBody,
  SelectControl,
  TextControl,
  RangeControl,
  Notice,
  Spinner,
  Button,
} = wp.components;
const { InspectorControls, useBlockProps } = wp.blockEditor;
const blockTypes = [
  {
    slug: "ad-slot",
    title: "Single Ad Slot",
    description: "Reference one persistent advertising slot.",
    icon: "align-wide",
    field: "slotId",
  },
  {
    slug: "ad-zone",
    title: "Advertising Zone",
    description: "Embed a published zone through one compact reference.",
    icon: "screenoptions",
    field: "zoneId",
  },
  {
    slug: "ad-repeater",
    title: "Automatic Repeater",
    description: "Insert advertising at a governed content interval.",
    icon: "editor-insertmore",
    field: "zoneId",
  },
  {
    slug: "ad-showcase",
    title: "Campaign Showcase",
    description:
      "Display an accessible static collection of approved inventory.",
    icon: "images-alt2",
    field: "zoneId",
  },
];
function BlockEditor({ attributes, setAttributes, definition }: any) {
  const [slotPage, setSlotPage] = wp.element.useState(1);
  const [slotSearch, setSlotSearch] = wp.element.useState("");
  const zones = useRemote<Page>("editor/zones?per_page=100");
  const slotQuery = new URLSearchParams({
    page: String(slotPage),
    per_page: "25",
    search: slotSearch,
  });
  const slots = useRemote<Page>(
    definition.field === "slotId" ? `catalog/placements?${slotQuery}` : null,
  );
  const [validation, setValidation] = wp.element.useState<{
    valid?: boolean;
    message?: string;
    error?: string;
  }>({});
  const choices =
    definition.field === "slotId" ? slots.data?.items : zones.data?.items;
  const selected = choices?.find(
    (item) => item.id === attributes[definition.field],
  );
  const props = useBlockProps({
    className: "adp-editor-reference",
    style: {
      border: "1px dashed #98a2b3",
      padding: "24px",
      background: "#f9fafb",
      color: "#1d2939",
    },
  });
  const loading =
    zones.loading || (definition.field === "slotId" && slots.loading);
  wp.element.useEffect(() => {
    const controller = new AbortController();
    if (!attributes[definition.field]) {
      setValidation({});
      return () => controller.abort();
    }
    const timer = setTimeout(() => {
      request<{ valid?: boolean; message?: string }>(
        "editor/placement-config/validate",
        {
          method: "POST",
          body: { block: `adpilot/${definition.slug}`, attributes },
          signal: controller.signal,
        },
      )
        .then((result) => {
          if (!controller.signal.aborted) setValidation(result);
        })
        .catch((error) => {
          if (!controller.signal.aborted)
            setValidation({ error: error.message });
        });
    }, 250);
    return () => {
      clearTimeout(timer);
      controller.abort();
    };
  }, [JSON.stringify(attributes)]);
  return (
    <>
      <InspectorControls>
        <PanelBody title={t("Placement settings")} initialOpen={true}>
          {loading && <Spinner />}
          {(zones.error || slots.error) && (
            <Notice status="error" isDismissible={false}>
              {zones.error?.message ?? slots.error?.message}
            </Notice>
          )}
          {definition.field === "slotId" && (
            <TextControl
              label={t("Search published slots")}
              value={slotSearch}
              onChange={(value: string) => {
                setSlotSearch(value);
                setSlotPage(1);
              }}
            />
          )}
          <SelectControl
            label={t(
              definition.field === "slotId"
                ? "Advertising slot"
                : "Published zone",
            )}
            value={attributes[definition.field] ?? ""}
            options={[
              { label: t("Select a placement…"), value: "" },
              ...(choices ?? []).map((item) => ({
                label:
                  item.name ??
                  `${item.zone_name ?? t("Slot")} ${item.ordinal ?? item.id}`,
                value: item.id,
              })),
            ]}
            onChange={(value: string) =>
              setAttributes({ [definition.field]: value })
            }
          />
          {definition.field === "slotId" && slots.data && (
            <div>
              <p>
                {slots.data.total} {t("published placements")} · {t("Page")}{" "}
                {slotPage} {t("of")}{" "}
                {Math.max(1, Math.ceil(slots.data.total / 25))}
              </p>
              <Button
                variant="secondary"
                disabled={slotPage <= 1}
                onClick={() => setSlotPage((value) => value - 1)}
              >
                {t("Previous")}
              </Button>{" "}
              <Button
                variant="secondary"
                disabled={slotPage * 25 >= slots.data.total}
                onClick={() => setSlotPage((value) => value + 1)}
              >
                {t("Next")}
              </Button>
            </div>
          )}
          {definition.slug === "ad-repeater" && (
            <RangeControl
              label={t("Insert after every eligible content item")}
              value={attributes.interval ?? 5}
              min={2}
              max={100}
              onChange={(interval: number) => setAttributes({ interval })}
            />
          )}
          <TextControl
            label={t("Editor label")}
            value={attributes.title ?? ""}
            onChange={(title: string) =>
              setAttributes({ title: title.slice(0, 160) })
            }
          />
          {validation.error && (
            <Notice status="error" isDismissible={false}>
              {validation.error}
            </Notice>
          )}
          {validation.valid === false && (
            <Notice status="warning" isDismissible={false}>
              {validation.message ??
                t(
                  "This configuration needs attention. Check the selected reference and supported context.",
                )}
            </Notice>
          )}
        </PanelBody>
      </InspectorControls>
      <div {...props}>
        <strong style={{ fontSize: "16px" }}>{t(definition.title)}</strong>
        <p>
          {selected?.name ??
            (attributes[definition.field]
              ? attributes[definition.field]
              : t("Choose a published placement in the block settings."))}
        </p>
        <p style={{ fontSize: "12px", marginBlockEnd: 0 }}>
          {t(
            "Representative editor preview · no advertising served · no measurement generated",
          )}
        </p>
        {definition.slug === "ad-repeater" && (
          <p style={{ fontSize: "12px" }}>
            {t("Eligible content interval")}: {attributes.interval ?? 5}.{" "}
            {t("Supported server contexts only.")}
          </p>
        )}
      </div>
    </>
  );
}
for (const definition of blockTypes) {
  wp.blocks.registerBlockType(`adpilot/${definition.slug}`, {
    apiVersion: 3,
    title: t(definition.title),
    description: t(definition.description),
    icon: definition.icon,
    category: "widgets",
    keywords: [t("advertising"), t("sponsorship"), t("placement")],
    attributes: {
      slotId: { type: "string", default: "" },
      zoneId: { type: "string", default: "" },
      containerId: { type: "string", default: "" },
      interval: { type: "number", default: 5 },
      title: { type: "string", default: "" },
    },
    supports: { html: false, customClassName: false },
    edit: (props: any) => <BlockEditor {...props} definition={definition} />,
    save: () => null,
  });
}
