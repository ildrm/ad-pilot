import test from "node:test";
import assert from "node:assert/strict";
import { createContext, runInContext } from "node:vm";
import { build } from "esbuild";

const bundle = await build({
  entryPoints: ["frontend/api.ts"],
  bundle: true,
  write: false,
  format: "iife",
  globalName: "AdPilotAPI",
});
function api(fetch, config = {}) {
  const context = createContext({
    window: {
      location: { origin: "https://publisher.test" },
      adpilotConfig: {
        restUrl: "https://publisher.test/wp-json/adpilot/v1/",
        nonce: "session-nonce",
        capabilities: [],
        ...config,
      },
    },
    wp: {},
    document: { documentElement: { lang: "en" } },
    fetch,
    URL,
    URLSearchParams,
    AbortController,
    DOMException,
    Intl,
  });
  runInContext(bundle.outputFiles[0].text, context);
  return context.AdPilotAPI;
}

test("authenticated commands use the site origin, nonce, JSON, and same-origin credentials", async () => {
  let received;
  const client = api(async (url, init) => {
    received = { url: String(url), init };
    return { ok: true, json: async () => ({ id: "saved" }) };
  });
  const result = await client.request("admin/layouts/drafts/A", {
    method: "PATCH",
    body: { row_version: 4, definition: { zones: [] } },
  });
  assert.equal(result.id, "saved");
  assert.equal(
    received.url,
    "https://publisher.test/wp-json/adpilot/v1/admin/layouts/drafts/A",
  );
  assert.equal(received.init.credentials, "same-origin");
  assert.equal(received.init.headers["X-WP-Nonce"], "session-nonce");
  assert.equal(received.init.headers["Content-Type"], "application/json");
  assert.deepEqual(JSON.parse(received.init.body), {
    row_version: 4,
    definition: { zones: [] },
  });
});

test("a foreign REST configuration cannot send the site nonce off-origin", async () => {
  let calls = 0;
  const client = api(
    async () => {
      calls++;
    },
    { restUrl: "https://attacker.test/api/" },
  );
  await assert.rejects(
    client.request("admin/settings"),
    (error) => error.code === "invalid_origin",
  );
  assert.equal(calls, 0);
});

test("WordPress query-style REST URLs preserve the namespace and list filters", async () => {
  let received;
  const client = api(
    async (url) => {
      received = new URL(url);
      return { ok: true, json: async () => ({ items: [] }) };
    },
    { restUrl: "https://publisher.test/index.php?rest_route=/adpilot/v1/" },
  );
  await client.request("admin/slots?page=2&per_page=25&container_id=ABC");
  assert.equal(received.pathname, "/index.php");
  assert.equal(
    received.searchParams.get("rest_route"),
    "/adpilot/v1/admin/slots",
  );
  assert.equal(received.searchParams.get("page"), "2");
  assert.equal(received.searchParams.get("per_page"), "25");
  assert.equal(received.searchParams.get("container_id"), "ABC");
});

test("WordPress conflict envelopes retain their actionable status and message", async () => {
  const client = api(async () => ({
    ok: false,
    status: 409,
    json: async () => ({
      code: "version_conflict",
      message: "This draft changed.",
      data: { status: 409 },
    }),
  }));
  await assert.rejects(
    client.request("admin/layouts/drafts/A", {
      method: "PATCH",
      body: { row_version: 1 },
    }),
    (error) =>
      error.status === 409 &&
      error.code === "version_conflict" &&
      error.message === "This draft changed.",
  );
});

test("an absolute foreign request path cannot leak the nonce", async () => {
  let calls = 0;
  const client = api(async () => {
    calls++;
  });
  await assert.rejects(
    client.request("https://attacker.test/steal"),
    (error) => error.code === "invalid_origin",
  );
  assert.equal(calls, 0);
});

test("network failures are recoverable and do not expose transport details", async () => {
  const client = api(async () => {
    throw new Error("private transport information");
  });
  await assert.rejects(
    client.request("admin/zones"),
    (error) =>
      error.code === "network_error" &&
      error.message.includes("Your changes are still here") &&
      !error.message.includes("private"),
  );
});

test("cancelled organization-scoped requests keep abort semantics", async () => {
  const abort = new DOMException("Cancelled", "AbortError");
  const client = api(async () => {
    throw abort;
  });
  await assert.rejects(
    client.request("organizations/ORG/creatives"),
    (error) => error === abort,
  );
});

test("exact minor-unit amounts never pass through floating point", () => {
  const client = api(async () => {});
  assert.equal(
    client.formatMoney("999999999999999999", "USD", 2),
    "USD 9,999,999,999,999,999.99",
  );
  assert.equal(client.formatMoney("7", "KWD", 3), "KWD 0.007");
  assert.equal(client.formatMoney("100", "JPY", 0), "JPY 100");
  assert.equal(
    client.formatMoney("100", "USD", undefined),
    "USD 100 minor units",
  );
});

test("hold commands preserve a caller-owned idempotency key across retries", async () => {
  const keys = [];
  const client = api(async (_url, init) => {
    keys.push(init.headers["Idempotency-Key"]);
    return { ok: true, json: async () => ({ id: "hold" }) };
  });
  const options = {
    method: "POST",
    body: { quote_id: "QUOTE" },
    idempotencyKey: "same-logical-hold-1234",
  };
  await client.request("organizations/ORG/holds", options);
  await client.request("organizations/ORG/holds", options);
  assert.deepEqual(keys, ["same-logical-hold-1234", "same-logical-hold-1234"]);
});
