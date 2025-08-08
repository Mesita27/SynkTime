using SourceAFIS;
using System.Drawing;

var builder = WebApplication.CreateBuilder(args);

// Add services
builder.Services.AddControllers();
builder.Services.AddEndpointsApiExplorer();
builder.Services.AddSwaggerGen();
builder.Services.AddSingleton<FingerprintService>();

// Add CORS
builder.Services.AddCors(options =>
{
    options.AddDefaultPolicy(policy =>
    {
        policy.AllowAnyOrigin()
              .AllowAnyMethod()
              .AllowAnyHeader();
    });
});

var app = builder.Build();

// Configure pipeline
if (app.Environment.IsDevelopment())
{
    app.UseSwagger();
    app.UseSwaggerUI();
}

app.UseCors();
app.UseRouting();
app.MapControllers();

app.Run();

// Fingerprint service
public class FingerprintService
{
    private readonly string _dataDir;
    private readonly Dictionary<string, FingerprintTemplate> _templates;

    public FingerprintService()
    {
        _dataDir = Path.Combine(Environment.CurrentDirectory, "data", "templates");
        Directory.CreateDirectory(_dataDir);
        _templates = new Dictionary<string, FingerprintTemplate>();
        LoadTemplates();
    }

    private void LoadTemplates()
    {
        foreach (var file in Directory.GetFiles(_dataDir, "*.template"))
        {
            try
            {
                var employeeId = Path.GetFileNameWithoutExtension(file);
                var bytes = File.ReadAllBytes(file);
                var template = new FingerprintTemplate(bytes);
                _templates[employeeId] = template;
            }
            catch (Exception ex)
            {
                Console.WriteLine($"Error loading template {file}: {ex.Message}");
            }
        }
    }

    public string Enroll(string employeeId, List<byte[]> imageBytes)
    {
        var templates = new List<FingerprintTemplate>();
        
        foreach (var imgBytes in imageBytes)
        {
            var image = new FingerprintImage(imgBytes);
            var template = new FingerprintTemplate(image);
            templates.Add(template);
        }

        // Use first template or merge if multiple
        var finalTemplate = templates.First();
        _templates[employeeId] = finalTemplate;

        // Save to disk
        var filePath = Path.Combine(_dataDir, $"{employeeId}.template");
        File.WriteAllBytes(filePath, finalTemplate.ToByteArray());

        return employeeId;
    }

    public List<MatchResult> Identify(byte[] imageBytes, int limit = 3)
    {
        var image = new FingerprintImage(imageBytes);
        var probe = new FingerprintTemplate(image);
        
        var matcher = new FingerprintMatcher(probe);
        var results = new List<MatchResult>();

        foreach (var kvp in _templates)
        {
            var score = matcher.Match(kvp.Value);
            results.Add(new MatchResult
            {
                EmployeeId = kvp.Key,
                FingerprintId = kvp.Key,
                Score = score
            });
        }

        return results.OrderByDescending(r => r.Score).Take(limit).ToList();
    }

    public MatchResult? Verify(byte[] imageBytes, string fingerprintId)
    {
        if (!_templates.ContainsKey(fingerprintId))
            return null;

        var image = new FingerprintImage(imageBytes);
        var probe = new FingerprintTemplate(image);
        var matcher = new FingerprintMatcher(probe);
        
        var score = matcher.Match(_templates[fingerprintId]);
        return new MatchResult
        {
            EmployeeId = fingerprintId,
            FingerprintId = fingerprintId,
            Score = score
        };
    }
}

public class MatchResult
{
    public string EmployeeId { get; set; } = "";
    public string FingerprintId { get; set; } = "";
    public double Score { get; set; }
}