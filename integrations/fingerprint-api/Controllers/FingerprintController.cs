using Microsoft.AspNetCore.Mvc;

[ApiController]
[Route("/")]
public class FingerprintController : ControllerBase
{
    private readonly FingerprintService _service;

    public FingerprintController(FingerprintService service)
    {
        _service = service;
    }

    [HttpPost("enroll")]
    public async Task<IActionResult> Enroll([FromForm] string employeeId, [FromForm] List<IFormFile> images)
    {
        try
        {
            if (string.IsNullOrEmpty(employeeId) || images == null || images.Count == 0)
            {
                return BadRequest(new { error = "employeeId and images are required" });
            }

            var imageBytes = new List<byte[]>();
            foreach (var file in images)
            {
                using var stream = new MemoryStream();
                await file.CopyToAsync(stream);
                imageBytes.Add(stream.ToArray());
            }

            var fingerprintId = _service.Enroll(employeeId, imageBytes);
            return Ok(new { fingerprintId, employeeId });
        }
        catch (Exception ex)
        {
            return StatusCode(500, new { error = ex.Message });
        }
    }

    [HttpPost("identify")]
    public async Task<IActionResult> Identify([FromForm] IFormFile image, [FromForm] int limit = 3)
    {
        try
        {
            if (image == null)
            {
                return BadRequest(new { error = "image file is required" });
            }

            using var stream = new MemoryStream();
            await image.CopyToAsync(stream);
            var imageBytes = stream.ToArray();

            var results = _service.Identify(imageBytes, limit);
            return Ok(new { candidates = results });
        }
        catch (Exception ex)
        {
            return StatusCode(500, new { error = ex.Message });
        }
    }

    [HttpPost("verify")]
    public async Task<IActionResult> Verify([FromForm] IFormFile image, [FromForm] string fingerprintId)
    {
        try
        {
            if (image == null || string.IsNullOrEmpty(fingerprintId))
            {
                return BadRequest(new { error = "image and fingerprintId are required" });
            }

            using var stream = new MemoryStream();
            await image.CopyToAsync(stream);
            var imageBytes = stream.ToArray();

            var result = _service.Verify(imageBytes, fingerprintId);
            if (result == null)
            {
                return NotFound(new { error = "fingerprintId not found" });
            }

            return Ok(result);
        }
        catch (Exception ex)
        {
            return StatusCode(500, new { error = ex.Message });
        }
    }

    [HttpGet("health")]
    public IActionResult Health()
    {
        return Ok(new { status = "healthy", timestamp = DateTime.UtcNow });
    }
}