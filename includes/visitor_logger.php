<?php
/**
 * Visitor Logger - Automatic visitor tracking
 * File: includes/visitor_logger.php
 * Description: Automatically logs visitor information when pages are accessed
 */

class VisitorLogger {
    private $pdo;
    private $logAnonymous = true; // Set to false to only log logged-in users
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Log visitor automatically
     */
    public function logVisitor($page = '') {
        try {
            // Get visitor information
            $visitorData = $this->getVisitorData($page);
            
            // Check if we should log this visitor
            if (!$this->shouldLogVisitor($visitorData)) {
                return false;
            }
            
            // Check if this IP has already been logged today for this page
            if ($this->isAlreadyLoggedToday($visitorData['ip_address'], $page)) {
                return false;
            }
            
            // Insert visitor log
            $stmt = $this->pdo->prepare("
                INSERT INTO visitors (
                    name, email, phone, institution, purpose, 
                    visit_date, check_in_time, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $visitorData['name'],
                $visitorData['email'],
                $visitorData['phone'],
                $visitorData['institution'],
                $visitorData['purpose'],
                $visitorData['visit_date'],
                $visitorData['check_in_time'],
                $visitorData['notes']
            ]);
            
            return true;
            
        } catch (Exception $e) {
            // Log error silently to avoid breaking the main application
            error_log("Visitor Logger Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get visitor data from various sources
     */
    private function getVisitorData($page) {
        $data = [
            'name' => 'Pengunjung Anonim',
            'email' => '',
            'phone' => '',
            'institution' => '',
            'purpose' => 'Kunjungan website',
            'visit_date' => date('Y-m-d'),
            'check_in_time' => date('H:i:s'),
            'notes' => '',
            'ip_address' => $this->getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'page' => $page
        ];
        
        // If user is logged in, get their information
        if (isset($_SESSION['user_id'])) {
            $userData = $this->getUserData($_SESSION['user_id']);
            if ($userData) {
                $data['name'] = $userData['full_name'];
                $data['email'] = $userData['email'];
                $data['phone'] = $userData['phone'] ?? '';
                $data['institution'] = 'Sistem Akademik';
                $data['purpose'] = 'Akses sistem sebagai ' . ucfirst($_SESSION['role']);
                $data['notes'] = 'User ID: ' . $_SESSION['user_id'] . ', Role: ' . $_SESSION['role'];
            }
        }
        
        // Customize purpose based on page
        $data['purpose'] = $this->getPagePurpose($page, $data['purpose']);
        
        return $data;
    }
    
    /**
     * Get user data from database
     */
    private function getUserData($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT full_name, email, phone, role 
                FROM users 
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Get client IP address
     */
    private function getClientIP() {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Check if we should log this visitor
     */
    private function shouldLogVisitor($visitorData) {
        // Don't log if logging is disabled for anonymous users
        if (!$this->logAnonymous && !isset($_SESSION['user_id'])) {
            return false;
        }
        
        // Don't log bots/crawlers
        if ($this->isBot($visitorData['user_agent'])) {
            return false;
        }
        
        // Don't log localhost/internal IPs
        if ($this->isLocalIP($visitorData['ip_address'])) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if user agent is a bot
     */
    private function isBot($userAgent) {
        $bots = [
            'bot', 'crawler', 'spider', 'scraper', 'slurp', 'baiduspider',
            'googlebot', 'bingbot', 'yandexbot', 'facebookexternalhit',
            'twitterbot', 'linkedinbot', 'whatsapp', 'telegram'
        ];
        
        $userAgent = strtolower($userAgent);
        foreach ($bots as $bot) {
            if (strpos($userAgent, $bot) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if IP is local/internal
     */
    private function isLocalIP($ip) {
        return in_array($ip, ['127.0.0.1', '::1', 'localhost']) ||
               filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE) === false;
    }
    
    /**
     * Check if this IP has already been logged today for this page
     */
    private function isAlreadyLoggedToday($ip, $page) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) 
                FROM visitors 
                WHERE visit_date = CURDATE() 
                AND notes LIKE ?
            ");
            $stmt->execute(['%IP: ' . $ip . '%']);
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get page-specific purpose
     */
    private function getPagePurpose($page, $defaultPurpose) {
        $pagePurposes = [
            'index.php' => 'Kunjungan halaman utama',
            'login.php' => 'Akses halaman login',
            'register.php' => 'Akses halaman registrasi',
            'visitor_log_form.php' => 'Mengisi form log pengunjung',
            'admin/dashboard.php' => 'Akses dashboard admin',
            'admin/visitor_stats.php' => 'Melihat statistik pengunjung',
            'siswa/dashboard.php' => 'Akses dashboard siswa'
        ];
        
        return $pagePurposes[$page] ?? $defaultPurpose;
    }
    
    /**
     * Get visitor statistics
     */
    public function getStats($period = 'today') {
        try {
            switch ($period) {
                case 'today':
                    $stmt = $this->pdo->prepare("
                        SELECT COUNT(*) as total, COUNT(DISTINCT name) as unique_visitors
                        FROM visitors 
                        WHERE visit_date = CURDATE()
                    ");
                    break;
                    
                case 'week':
                    $stmt = $this->pdo->prepare("
                        SELECT COUNT(*) as total, COUNT(DISTINCT name) as unique_visitors
                        FROM visitors 
                        WHERE YEARWEEK(visit_date, 1) = YEARWEEK(CURDATE(), 1)
                    ");
                    break;
                    
                case 'month':
                    $stmt = $this->pdo->prepare("
                        SELECT COUNT(*) as total, COUNT(DISTINCT name) as unique_visitors
                        FROM visitors 
                        WHERE YEAR(visit_date) = YEAR(CURDATE()) 
                        AND MONTH(visit_date) = MONTH(CURDATE())
                    ");
                    break;
                    
                default:
                    return null;
            }
            
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Get recent visitors
     */
    public function getRecentVisitors($limit = 10) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT name, email, institution, purpose, visit_date, check_in_time
                FROM visitors 
                ORDER BY visit_date DESC, check_in_time DESC 
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
}

// Auto-initialize if included directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    require_once '../db.php';
    $logger = new VisitorLogger($pdo);
    $logger->logVisitor(basename($_SERVER['SCRIPT_FILENAME']));
}
?> 