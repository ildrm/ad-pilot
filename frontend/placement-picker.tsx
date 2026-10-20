import { Entity, listPath, Page, t, useRemote } from "./api";
import { Field, Notice, Pager } from "./ui";

/** Bounded chooser: every published slot remains reachable, including 500+ inventories. */
export function PlacementPicker({
  value,
  onChange,
}: {
  value: string;
  onChange: (value: string) => void;
}) {
  const [page, setPage] = wp.element.useState(1);
  const [search, setSearch] = wp.element.useState("");
  const [selected, setSelected] = wp.element.useState<Entity | null>(null);
  const placements = useRemote<Page>(
    listPath("catalog/placements", page, search, { per_page: "10" }),
  );
  const items = placements.data?.items ?? [];
  return (
    <fieldset className="adp-placement-picker">
      <legend>{t("Published placement")}</legend>
      <Field label={t("Find placement")}>
        <input
          type="search"
          onKeyDown={(event: any) => {
            if (event.key === "Enter") event.preventDefault();
          }}
          value={search}
          placeholder={t("Search by zone, name, or identity…")}
          onChange={(event: any) => {
            setSearch(event.target.value);
            setPage(1);
          }}
        />
      </Field>
      <Field
        label={t("Placement results")}
        hint={t(
          "Search or change page to reach all published slots. Your selection is kept while browsing.",
        )}
      >
        <select
          size={6}
          value={items.some((item) => item.id === value) ? value : ""}
          aria-busy={placements.loading}
          onChange={(event: any) => {
            const item = items.find((item) => item.id === event.target.value);
            if (item) {
              setSelected(item);
              onChange(item.id);
            }
          }}
        >
          <option value="" disabled>
            {placements.loading
              ? t("Loading placements…")
              : items.length
                ? t("Choose a placement")
                : t("No matching placements")}
          </option>
          {items.map((item) => (
            <option value={item.id} key={item.id}>
              {item.name ?? item.id}
            </option>
          ))}
        </select>
      </Field>
      <Notice error={placements.error} />
      {placements.data && (
        <Pager
          perPage={10}
          page={page}
          total={placements.data.total}
          setPage={setPage}
        />
      )}
      <p className="adp-selected-placement" role="status">
        {value
          ? `${t("Selected")}: ${selected?.name ?? value}`
          : t("No placement selected")}
      </p>
    </fieldset>
  );
}
