/**
 * Response-header / status assertions for the headers (Cache-Control, CSP, CORS)
 * and hardening (403/200) specs — the Playwright equivalent of `cy.request(...)`
 * followed by `expect(response.headers[...])`.
 *
 * Pass the authenticated `request` fixture for logged-in checks or the
 * `loggedOutRequest` fixture for anonymous checks. Playwright lowercases header
 * names, so always query lowercase (e.g. 'cache-control').
 */
import { expect, type APIRequestContext } from "@playwright/test";

interface ResponseExpectation {
  status?: number;
}

async function fetch(
  request: APIRequestContext,
  path: string,
  options: ResponseExpectation = {},
) {
  const response = await request.get(path, { failOnStatusCode: false });
  expect(response.status()).toBe(options.status ?? 200);
  const finalUrl = new URL(response.url());
  expect(`${finalUrl.pathname}${finalUrl.search}`).toBe(path);
  return response;
}

/** Assert a response header equals an exact value. */
export async function expectHeaderEquals(
  request: APIRequestContext,
  path: string,
  name: string,
  value: string,
  options?: ResponseExpectation,
): Promise<void> {
  const response = await fetch(request, path, options);
  expect(response.headers()[name.toLowerCase()]).toBe(value);
}

/** Assert a response header contains a substring. */
export async function expectHeaderContains(
  request: APIRequestContext,
  path: string,
  name: string,
  substring: string,
  options?: ResponseExpectation,
): Promise<void> {
  const response = await fetch(request, path, options);
  expect(response.headers()[name.toLowerCase()] ?? "").toContain(substring);
}

/** Assert a response header is NOT present. */
export async function expectHeaderAbsent(
  request: APIRequestContext,
  path: string,
  name: string,
  options?: ResponseExpectation,
): Promise<void> {
  const response = await fetch(request, path, options);
  expect(response.headers()[name.toLowerCase()]).toBeUndefined();
}

/** Assert a path is blocked (403 Forbidden) — hardening allowlist removed. */
export async function expectForbidden(
  request: APIRequestContext,
  path: string,
): Promise<void> {
  const response = await fetch(request, path, { status: 403 });
  expect(await response.text()).toContain("Forbidden");
}

/** Assert a path is served (200) and renders the seeded "Hello, world!" payload. */
export async function expectHelloWorld(
  request: APIRequestContext,
  path: string,
): Promise<void> {
  const response = await fetch(request, path);
  expect(response.status()).toBe(200);
  expect(await response.text()).toContain("Hello, world!");
}
