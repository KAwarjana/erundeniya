<?php
// setup_timeslots.php
// Run this script once to generate time slots for the next few months

require_once 'connection/connection.php'; 
require_once 'appointment_handler.php';

class TimeSlotSetup {
    
    /**
     * Generate time slots for the next N months
     */
    public static function generateFutureSlots($months = 3) {
        $startDate = new DateTime();
        $endDate = new DateTime();
        $endDate->add(new DateInterval("P{$months}M"));
        
        $current = clone $startDate;
        $slotsCreated = 0;
        
        echo "Generating time slots from " . $startDate->format('Y-m-d') . " to " . $endDate->format('Y-m-d') . "\n";
        
        while ($current <= $endDate) {
            $dayOfWeek = $current->format('N'); // 1 = Monday, 7 = Sunday
            
            // Generate slots for Wednesday (3) or Sunday (7)
            if ($dayOfWeek == 3 || $dayOfWeek == 7) {
                $date = $current->format('Y-m-d');
                $result = AppointmentManager::createTimeSlotsForDate($date);
                
                if ($result) {
                    $dayName = $current->format('l');
                    echo "Created slots for $dayName, $date\n";
                    $slotsCreated++;
                }
            }
            
            $current->add(new DateInterval('P1D'));
        }
        
        echo "Setup complete! Created slots for $slotsCreated consultation days.\n";
    }
    
    /**
     * Clean up old time slots (older than 30 days)
     */
    public static function cleanupOldSlots() {
        try {
            $cutoffDate = date('Y-m-d', strtotime('-30 days'));
            
            // Delete old slots that are not booked
            $deleteQuery = "DELETE ts FROM time_slots ts 
                           LEFT JOIN appointment a ON ts.id = a.slot_id 
                           WHERE ts.slot_date < '$cutoffDate' AND a.id IS NULL";
            
            Database::iud($deleteQuery);
            
            echo "Cleaned up old time slots before $cutoffDate\n";
        } catch (Exception $e) {
            echo "Error cleaning up old slots: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Get statistics about time slots
     */
    public static function getSlotStatistics() {
        try {
            // Total slots
            $totalQuery = "SELECT COUNT(*) as total FROM time_slots";
            $totalResult = Database::search($totalQuery);
            $total = $totalResult->fetch_assoc()['total'];
            
            // Available slots
            $availableQuery = "SELECT COUNT(*) as available FROM time_slots ts 
                              LEFT JOIN appointment a ON ts.id = a.slot_id AND a.status NOT IN ('Cancelled', 'No-Show')
                              WHERE a.id IS NULL AND ts.slot_date >= CURDATE()";
            $availableResult = Database::search($availableQuery);
            $available = $availableResult->fetch_assoc()['available'];
            
            // Booked slots
            $bookedQuery = "SELECT COUNT(*) as booked FROM appointment 
                           WHERE status NOT IN ('Cancelled', 'No-Show') AND appointment_date >= CURDATE()";
            $bookedResult = Database::search($bookedQuery);
            $booked = $bookedResult->fetch_assoc()['booked'];
            
            echo "\n=== Time Slot Statistics ===\n";
            echo "Total slots in database: $total\n";
            echo "Available future slots: $available\n";
            echo "Booked future slots: $booked\n";
            
            // Next few consultation dates
            echo "\n=== Next Consultation Dates ===\n";
            $dates = AppointmentManager::getNextConsultationDates(5);
            foreach ($dates as $date) {
                // Count available slots for this date
                $dateAvailableQuery = "SELECT COUNT(*) as available FROM time_slots ts 
                                      LEFT JOIN appointment a ON ts.id = a.slot_id AND a.status NOT IN ('Cancelled', 'No-Show')
                                      WHERE ts.slot_date = '{$date['date']}' AND a.id IS NULL";
                $dateAvailableResult = Database::search($dateAvailableQuery);
                $dateAvailable = $dateAvailableResult->fetch_assoc()['available'];
                
                echo $date['display_date'] . ": $dateAvailable available slots\n";
            }
            
        } catch (Exception $e) {
            echo "Error getting statistics: " . $e->getMessage() . "\n";
        }
    }
}

// Command line interface
if (php_sapi_name() === 'cli') {
    $command = $argv[1] ?? 'help';
    
    switch ($command) {
        case 'generate':
            $months = $argv[2] ?? 3;
            TimeSlotSetup::generateFutureSlots($months);
            break;
            
        case 'cleanup':
            TimeSlotSetup::cleanupOldSlots();
            break;
            
        case 'stats':
            TimeSlotSetup::getSlotStatistics();
            break;
            
        case 'help':
        default:
            echo "Usage: php setup_timeslots.php [command] [options]\n\n";
            echo "Commands:\n";
            echo "  generate [months]  - Generate time slots for next N months (default: 3)\n";
            echo "  cleanup           - Remove old time slots\n";
            echo "  stats             - Show time slot statistics\n";
            echo "  help              - Show this help message\n\n";
            echo "Examples:\n";
            echo "  php setup_timeslots.php generate 6    # Generate slots for next 6 months\n";
            echo "  php setup_timeslots.php cleanup       # Clean up old slots\n";
            echo "  php setup_timeslots.php stats         # Show statistics\n";
            break;
    }
} else {
    // Web interface for manual setup
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Time Slot Setup</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 40px; }
            .container { max-width: 800px; margin: 0 auto; }
            .button { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px; }
            .success { background: #28a745; color: white; padding: 15px; border-radius: 5px; margin: 10px 0; }
            .error { background: #dc3545; color: white; padding: 15px; border-radius: 5px; margin: 10px 0; }
            pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Erundeniya Ayurveda Hospital - Time Slot Setup</h1>
            
            <?php
            if (isset($_GET['action'])) {
                echo "<div>";
                ob_start();
                
                switch ($_GET['action']) {
                    case 'generate':
                        $months = $_GET['months'] ?? 3;
                        TimeSlotSetup::generateFutureSlots($months);
                        break;
                        
                    case 'cleanup':
                        TimeSlotSetup::cleanupOldSlots();
                        break;
                        
                    case 'stats':
                        TimeSlotSetup::getSlotStatistics();
                        break;
                }
                
                $output = ob_get_clean();
                echo "<pre>" . htmlspecialchars($output) . "</pre>";
                echo "</div>";
            }
            ?>
            
            <h2>Available Actions</h2>
            <a href="?action=generate&months=3" class="button">Generate 3 Months Slots</a>
            <a href="?action=generate&months=6" class="button">Generate 6 Months Slots</a>
            <a href="?action=cleanup" class="button">Cleanup Old Slots</a>
            <a href="?action=stats" class="button">Show Statistics</a>
            
            <h2>Instructions</h2>
            <ol>
                <li>Click "Generate 3 Months Slots" to create time slots for the next 3 months</li>
                <li>Use "Show Statistics" to see current slot availability</li>
                <li>Run "Cleanup Old Slots" periodically to remove outdated slots</li>
                <li>For automatic setup, you can run this script from command line</li>
            </ol>
            
            <h2>Schedule Information</h2>
            <p><strong>Consultation Days:</strong> Wednesday and Sunday</p>
            <p><strong>Time:</strong> 9:00 AM to 8:00 PM</p>
            <p><strong>Slot Duration:</strong> 10 minutes</p>
            <p><strong>Total Slots per Day:</strong> 66 slots</p>
        </div>
    </body>
    </html>
    <?php
}
?>