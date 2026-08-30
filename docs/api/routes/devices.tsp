using TypeSpec.Http;
using TypeSpec.OpenAPI;

namespace Yotee;

@tag("Devices")
@route("/devices")
@useAuth(BearerAuth)
interface Devices {
  /**
   * Register (or re-register) a push token for the caller's device. Its only
   * server-side use is delivering `category.updated` sync pings — never
   * reminder payloads (PRD §26).
   */
  @post
  register(@body body: RegisterDeviceRequest): {
    @statusCode statusCode: 201;
    @body device: Device;
  } | ErrorResponse;

  /** Unregister a device, e.g. on logout. */
  @delete
  @route("/{id}")
  unregister(@path id: string): {
    @statusCode statusCode: 204;
  } | ErrorResponse;
}
