# Third-party notices

AdPilot source is GPL-2.0-or-later; see LICENSE. WordPress supplies React and
its editor libraries; those are not bundled as duplicate runtimes in AdPilot.

Build tools are pinned in package-lock.json:

- esbuild 0.25.11, MIT: https://github.com/evanw/esbuild
- TypeScript 5.9.3, Apache-2.0: https://github.com/microsoft/TypeScript

These development packages and their platform binaries are excluded from the
plugin ZIP. No remote fonts, stock images or third-party analytics scripts are
included. The UI uses system fonts and WordPress APIs. License copies for
development tools are available in node_modules after npm ci.

WordPress/WooCommerce compatibility, provider approval and public naming
clearance are separate release checks; a package license is not certification.
