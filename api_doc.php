<?php
session_start();
require_once "db.php";

// ต้อง login ก่อน
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$role = $_SESSION['role'];

// สร้าง API Key ถ้ายังไม่มี (ใช้ user_id + random)
$user_id = $_SESSION['user_id'];
$api_key = md5($user_id . 'certificate_api_' . date('Y-m-d'));
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>API Documentation - Certificate Designer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4e73df;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .api-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .api-header {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .api-key-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-top: 1rem;
        }
        
        .api-key-display {
            background: rgba(255,255,255,0.2);
            padding: 1rem;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            word-break: break-all;
            margin-top: 1rem;
        }
        
        .endpoint-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease;
        }
        
        .endpoint-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .method-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.875rem;
            margin-right: 1rem;
        }
        
        .method-get { background: var(--info-color); color: white; }
        .method-post { background: var(--success-color); color: white; }
        .method-put { background: var(--warning-color); color: white; }
        .method-delete { background: var(--danger-color); color: white; }
        
        .code-block {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 1.5rem;
            border-radius: 8px;
            overflow-x: auto;
            margin: 1rem 0;
        }
        
        pre {
            margin: 0;
        }
        
        .nav-pills .nav-link {
            color: #667eea;
            border-radius: 20px;
            padding: 0.75rem 1.5rem;
            margin-right: 0.5rem;
        }
        
        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .copy-btn {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .copy-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .response-example {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid var(--primary-color);
        }
        
        .param-table {
            margin: 1rem 0;
        }
        
        .param-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="api-container">
        <!-- Header -->
        <div class="api-header">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="mb-0"><i class="fas fa-code"></i> API Documentation</h1>
                    <p class="text-muted mb-0">Certificate Designer System API v2.0</p>
                </div>
                <a href="dashboard.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left"></i> กลับสู่ Dashboard
                </a>
            </div>
            
            <!-- API Key Section -->
            <div class="api-key-box">
                <h5><i class="fas fa-key"></i> Your API Key</h5>
                <p class="mb-2">ใช้ API Key นี้สำหรับการเชื่อมต่อกับระบบ</p>
                <div class="api-key-display">
                    <strong>API Key:</strong> <span id="apiKey"><?php echo $api_key; ?></span>
                    <button class="btn btn-sm btn-light float-end" onclick="copyApiKey()">
                        <i class="fas fa-copy"></i> Copy
                    </button>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <ul class="nav nav-pills mb-4 bg-white p-3 rounded-3 shadow-sm" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#overview">
                    <i class="fas fa-info-circle"></i> Overview
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#authentication">
                    <i class="fas fa-shield-alt"></i> Authentication
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#endpoints">
                    <i class="fas fa-network-wired"></i> Endpoints
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#examples">
                    <i class="fas fa-code"></i> Examples
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content">
            <!-- Overview Tab -->
            <div class="tab-pane fade show active" id="overview">
                <div class="endpoint-card">
                    <h3><i class="fas fa-book-open"></i> API Overview</h3>
                    <p class="lead">Certificate Designer System API ให้คุณสามารถจัดการใบประกาศผ่าน HTTP REST API</p>
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <h5>Base URL</h5>
                            <div class="code-block">
                                <code>http://<?php echo $_SERVER['HTTP_HOST']; ?>/certificate/api/</code>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h5>Content Type</h5>
                            <div class="code-block">
                                <code>application/json</code>
                            </div>
                        </div>
                    </div>
                    
                    <h5 class="mt-4">Features</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-check text-success"></i> RESTful API Design</li>
                        <li><i class="fas fa-check text-success"></i> JSON Request/Response</li>
                        <li><i class="fas fa-check text-success"></i> API Key Authentication</li>
                        <li><i class="fas fa-check text-success"></i> Full CRUD Operations</li>
                        <li><i class="fas fa-check text-success"></i> Certificate Verification</li>
                    </ul>
                </div>
            </div>

            <!-- Authentication Tab -->
            <div class="tab-pane fade" id="authentication">
                <div class="endpoint-card">
                    <h3><i class="fas fa-shield-alt"></i> Authentication</h3>
                    <p>ทุก API request ต้องมี API Key เพื่อยืนยันตัวตน</p>
                    
                    <h5 class="mt-4">วิธีที่ 1: Query Parameter</h5>
                    <div class="code-block">
                        <pre><code class="language-bash">GET /api/certificates.php?api_key=YOUR_API_KEY</code></pre>
                    </div>
                    
                    <h5 class="mt-4">วิธีที่ 2: HTTP Header (Recommended)</h5>
                    <div class="code-block">
                        <pre><code class="language-bash">X-API-Key: YOUR_API_KEY</code></pre>
                    </div>
                    
                    <div class="alert alert-warning mt-4">
                        <i class="fas fa-exclamation-triangle"></i> <strong>Warning:</strong> เก็บ API Key ของคุณไว้เป็นความลับ อย่าแชร์ในที่สาธารณะ
                    </div>
                </div>
            </div>

            <!-- Endpoints Tab -->
            <div class="tab-pane fade" id="endpoints">
                <!-- GET Certificates -->
                <div class="endpoint-card">
                    <div class="d-flex align-items-center mb-3">
                        <span class="method-badge method-get">GET</span>
                        <h4 class="mb-0">/api/certificates.php</h4>
                    </div>
                    <p>ดึงข้อมูลใบประกาศทั้งหมดหรือเฉพาะ ID</p>
                    
                    <h6>Parameters</h6>
                    <table class="table param-table table-bordered">
                        <thead>
                            <tr>
                                <th>Parameter</th>
                                <th>Type</th>
                                <th>Required</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>api_key</code></td>
                                <td>string</td>
                                <td>Yes</td>
                                <td>Your API Key</td>
                            </tr>
                            <tr>
                                <td><code>id</code></td>
                                <td>integer</td>
                                <td>No</td>
                                <td>Certificate ID (specific certificate)</td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <h6>Response Example</h6>
                    <div class="code-block">
<pre><code class="language-json">{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "นายสมชาย ใจดี",
      "qr_code": "https://...",
      "verification_code": "CERT-2025-ABC123",
      "created_at": "2025-10-15 10:30:00"
    }
  ],
  "total": 100
}</code></pre>
                    </div>
                </div>

                <!-- POST Certificate -->
                <div class="endpoint-card">
                    <div class="d-flex align-items-center mb-3">
                        <span class="method-badge method-post">POST</span>
                        <h4 class="mb-0">/api/certificates.php</h4>
                    </div>
                    <p>สร้างใบประกาศใหม่</p>
                    
                    <h6>Request Body</h6>
                    <div class="code-block">
<pre><code class="language-json">{
  "name": "นายสมชาย ใจดี",
  "user_id": 1
}</code></pre>
                    </div>
                    
                    <h6>Headers</h6>
                    <div class="code-block">
<pre><code class="language-bash">Content-Type: application/json
X-API-Key: YOUR_API_KEY</code></pre>
                    </div>
                    
                    <h6>Response Example</h6>
                    <div class="code-block">
<pre><code class="language-json">{
  "success": true,
  "message": "Certificate created successfully",
  "id": 124
}</code></pre>
                    </div>
                </div>

                <!-- PUT Certificate -->
                <div class="endpoint-card">
                    <div class="d-flex align-items-center mb-3">
                        <span class="method-badge method-put">PUT</span>
                        <h4 class="mb-0">/api/certificates.php?id=123</h4>
                    </div>
                    <p>อัปเดตข้อมูลใบประกาศ</p>
                    
                    <h6>Request Body</h6>
                    <div class="code-block">
<pre><code class="language-json">{
  "name": "นายสมชาย ใจดี (แก้ไข)"
}</code></pre>
                    </div>
                    
                    <h6>Response Example</h6>
                    <div class="code-block">
<pre><code class="language-json">{
  "success": true,
  "message": "Certificate updated successfully"
}</code></pre>
                    </div>
                </div>

                <!-- DELETE Certificate -->
                <div class="endpoint-card">
                    <div class="d-flex align-items-center mb-3">
                        <span class="method-badge method-delete">DELETE</span>
                        <h4 class="mb-0">/api/certificates.php?id=123</h4>
                    </div>
                    <p>ลบใบประกาศ</p>
                    
                    <h6>Parameters</h6>
                    <table class="table param-table table-bordered">
                        <thead>
                            <tr>
                                <th>Parameter</th>
                                <th>Type</th>
                                <th>Required</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>id</code></td>
                                <td>integer</td>
                                <td>Yes</td>
                                <td>Certificate ID to delete</td>
                            </tr>
                            <tr>
                                <td><code>api_key</code></td>
                                <td>string</td>
                                <td>Yes</td>
                                <td>Your API Key</td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <h6>Response Example</h6>
                    <div class="code-block">
<pre><code class="language-json">{
  "success": true,
  "message": "Certificate deleted successfully"
}</code></pre>
                    </div>
                </div>

                <!-- Verify Certificate -->
                <div class="endpoint-card">
                    <div class="d-flex align-items-center mb-3">
                        <span class="method-badge method-post">POST</span>
                        <h4 class="mb-0">/api/verify.php</h4>
                    </div>
                    <p>ตรวจสอบความถูกต้องของใบประกาศ</p>
                    
                    <h6>Request Body</h6>
                    <div class="code-block">
<pre><code class="language-json">{
  "verification_code": "CERT-2025-ABC123"
}</code></pre>
                    </div>
                    
                    <h6>Response Example (Valid)</h6>
                    <div class="code-block">
<pre><code class="language-json">{
  "success": true,
  "valid": true,
  "data": {
    "name": "นายสมชาย ใจดี",
    "issued_date": "2025-10-15",
    "organization": "มหาวิทยาลัย ABC"
  }
}</code></pre>
                    </div>
                </div>
            </div>

            <!-- Examples Tab -->
            <div class="tab-pane fade" id="examples">
                <!-- cURL Examples -->
                <div class="endpoint-card">
                    <h3><i class="fab fa-linux"></i> cURL Examples</h3>
                    
                    <h5 class="mt-4">Get All Certificates</h5>
                    <div class="code-block position-relative">
                        <button class="copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i></button>
<pre><code class="language-bash">curl -X GET "http://<?php echo $_SERVER['HTTP_HOST']; ?>/certificate/api/certificates.php?api_key=<?php echo $api_key; ?>"</code></pre>
                    </div>
                    
                    <h5 class="mt-4">Create Certificate</h5>
                    <div class="code-block position-relative">
                        <button class="copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i></button>
<pre><code class="language-bash">curl -X POST "http://<?php echo $_SERVER['HTTP_HOST']; ?>/certificate/api/certificates.php" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: <?php echo $api_key; ?>" \
  -d '{"name":"นายสมชาย ใจดี","user_id":<?php echo $user_id; ?>}'</code></pre>
                    </div>
                    
                    <h5 class="mt-4">Verify Certificate</h5>
                    <div class="code-block position-relative">
                        <button class="copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i></button>
<pre><code class="language-bash">curl -X POST "http://<?php echo $_SERVER['HTTP_HOST']; ?>/certificate/api/verify.php" \
  -H "Content-Type: application/json" \
  -d '{"verification_code":"CERT-2025-ABC123"}'</code></pre>
                    </div>
                </div>

                <!-- JavaScript Examples -->
                <div class="endpoint-card">
                    <h3><i class="fab fa-js"></i> JavaScript (Fetch API) Examples</h3>
                    
                    <h5 class="mt-4">Get All Certificates</h5>
                    <div class="code-block position-relative">
                        <button class="copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i></button>
<pre><code class="language-javascript">fetch('http://<?php echo $_SERVER['HTTP_HOST']; ?>/certificate/api/certificates.php?api_key=<?php echo $api_key; ?>')
  .then(response => response.json())
  .then(data => console.log(data))
  .catch(error => console.error('Error:', error));</code></pre>
                    </div>
                    
                    <h5 class="mt-4">Create Certificate</h5>
                    <div class="code-block position-relative">
                        <button class="copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i></button>
<pre><code class="language-javascript">fetch('http://<?php echo $_SERVER['HTTP_HOST']; ?>/certificate/api/certificates.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-API-Key': '<?php echo $api_key; ?>'
  },
  body: JSON.stringify({
    name: 'นายสมชาย ใจดี',
    user_id: <?php echo $user_id; ?>
  })
})
  .then(response => response.json())
  .then(data => console.log(data))
  .catch(error => console.error('Error:', error));</code></pre>
                    </div>
                </div>

                <!-- PHP Examples -->
                <div class="endpoint-card">
                    <h3><i class="fab fa-php"></i> PHP Examples</h3>
                    
                    <h5 class="mt-4">Get All Certificates</h5>
                    <div class="code-block position-relative">
                        <button class="copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i></button>
<pre><code class="language-php">$api_key = '<?php echo $api_key; ?>';
$url = 'http://<?php echo $_SERVER['HTTP_HOST']; ?>/certificate/api/certificates.php?api_key=' . $api_key;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
print_r($data);</code></pre>
                    </div>
                </div>

                <!-- Python Examples -->
                <div class="endpoint-card">
                    <h3><i class="fab fa-python"></i> Python Examples</h3>
                    
                    <h5 class="mt-4">Get All Certificates</h5>
                    <div class="code-block position-relative">
                        <button class="copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i></button>
<pre><code class="language-python">import requests

api_key = '<?php echo $api_key; ?>'
url = 'http://<?php echo $_SERVER['HTTP_HOST']; ?>/certificate/api/certificates.php'

response = requests.get(url, params={'api_key': api_key})
data = response.json()
print(data)</code></pre>
                    </div>
                    
                    <h5 class="mt-4">Create Certificate</h5>
                    <div class="code-block position-relative">
                        <button class="copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i></button>
<pre><code class="language-python">import requests

api_key = '<?php echo $api_key; ?>'
url = 'http://<?php echo $_SERVER['HTTP_HOST']; ?>/certificate/api/certificates.php'

headers = {
    'Content-Type': 'application/json',
    'X-API-Key': api_key
}

data = {
    'name': 'นายสมชาย ใจดี',
    'user_id': <?php echo $user_id; ?>
}

response = requests.post(url, json=data, headers=headers)
result = response.json()
print(result)</code></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-bash.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-json.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-php.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-python.min.js"></script>
    <script>
        function copyApiKey() {
            const apiKey = document.getElementById('apiKey').textContent;
            navigator.clipboard.writeText(apiKey).then(() => {
                alert('API Key copied to clipboard!');
            });
        }
        
        function copyCode(button) {
            const codeBlock = button.nextElementSibling;
            const code = codeBlock.textContent;
            navigator.clipboard.writeText(code).then(() => {
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check"></i> Copied!';
                setTimeout(() => {
                    button.innerHTML = originalText;
                }, 2000);
            });
        }
    </script>
</body>
</html>
