import { build } from "esbuild";
import { mkdir, copyFile } from "node:fs/promises";

await mkdir("assets", { recursive: true });
await build({
  entryPoints: { admin: "frontend/app.tsx", blocks: "frontend/blocks.tsx" },
  outdir: "assets",
  bundle: true,
  minify: true,
  target: ["es2020"],
  jsxFactory: "wp.element.createElement",
  jsxFragment: "wp.element.Fragment",
  legalComments: "eof",
  sourcemap: false,
});
await copyFile("frontend/admin.css", "assets/admin.css");
console.log(
  "Built isolated console and Gutenberg bundles using WordPress React.",
);
