<?php
// simple_test.php - Create this file first to test basic functionality
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in JSON response

try {
    // Test database connection
    class Database {
        public static $connection;
        
        private static $host = "localhost";
        private static $username = "root";
        private static $password = "Kawi@#$123";
        private static $database = "erundeniya";
        private static $port = "3306";

        public static function setUpConnection() {
            if (!isset(Database::$connection)) {
                Database::$connection = new mysqli(
                    self::$host, 
                    self::$username, 
                    self::$password, 
                    self::$database, 
                    self::$port
                );
                
                if (Database::$connection->connect_error) {
                    throw new Exception("Database connection failed: " . Database::$connection->connect_error);
                }
                
                Database::$connection->set_charset("utf8mb4");
            }
        }

        public static function search($q) {
            Database::setUpConnection();
            $result = Database::$connection->query($q);
            if (!$result) {
                throw new Exception("Query failed: " . Database::$connection->error);
            }
            return $result;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'test':
                echo json_encode([
                    'success' => true, 
                    'message' => 'PHP file is working', 
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
                break;
                
            case 'test_db':
                Database::setUpConnection();
                echo json_encode([
                    'success' => true, 
                    'message' => 'Database connection successful'
                ]);
                break;
                
            case 'get_consultation_dates':
                // Simple hardcoded test dates
                $dates = [];
                $currentDate = new DateTime();
                
                for ($i = 0; $i < 4; $i++) {
                    $dayOfWeek = $currentDate->format('N');
                    
                    if ($dayOfWeek == 3 || $dayOfWeek == 7) { // Wednesday or Sunday
                        $dates[] = [
                            'date' => $currentDate->format('Y-m-d'),
                            'display_date' => $currentDate->format('l, j M Y'),
                            'day_name' => $currentDate->format('l')
                        ];
                    }
                    
                    $currentDate->add(new DateInterval('P1D'));
                    
                    if (count($dates) >= 2) break; // Get 2 consultation dates
                }
                
                echo json_encode(['success' => true, 'dates' => $dates]);
                break;
                
            case 'get_time_slots':
                // Simple test slots
                $slots = [
                    [
                        'id' => 1,
                        'time' => '09:00:00',
                        'display_time' => '9:00 AM',
                        'is_available' => true,
                        'appointment_number' => null
                    ],
                    [
                        'id' => 2,
                        'time' => '09:10:00',
                        'display_time' => '9:10 AM',
                        'is_available' => true,
                        'appointment_number' => null
                    ],
                    [
                        'id' => 3,
                        'time' => '09:20:00',
                        'display_time' => '9:20 AM',
                        'is_available' => false,
                        'appointment_number' => 'APT001'
                    ]
                ];
                
                echo json_encode(['success' => true, 'slots' => $slots]);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
        }
    } else {
        // GET request - show test page
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Simple Test</title>
        </head>
        <body>
            <h1>Test Results</h1>
            <div id="results"></div>
            
            <script>
            const results = document.getElementById('results');
            
            async function testEndpoint(action, description) {
                try {
                    const formData = new FormData();
                    formData.append('action', action);
                    
                    const response = await fetch('simple_test.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const text = await response.text();
                    console.log(description + ' raw response:', text);
                    
                    const data = JSON.parse(text);
                    
                    results.innerHTML += `<p><strong>${description}:</strong> ${data.success ? 'SUCCESS' : 'FAILED'}</p>`;
                    if (data.message) {
                        results.innerHTML += `<p>Message: ${data.message}</p>`;
                    }
                    if (data.dates) {
                        results.innerHTML += `<p>Found ${data.dates.length} consultation dates</p>`;
                    }
                    if (data.slots) {
                        results.innerHTML += `<p>Found ${data.slots.length} time slots</p>`;
                    }
                    
                } catch (error) {
                    results.innerHTML += `<p><strong>${description}:</strong> ERROR - ${error.message}</p>`;
                    console.error(description + ' error:', error);
                }
                
                results.innerHTML += '<hr>';
            }
            
            // Run tests
            (async () => {
                await testEndpoint('test', 'Basic PHP Test');
                await testEndpoint('test_db', 'Database Connection Test');
                await testEndpoint('get_consultation_dates', 'Get Consultation Dates');
                await testEndpoint('get_time_slots', 'Get Time Slots');
            })();
            </script>
        </body>
        </html>
        <?php
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>