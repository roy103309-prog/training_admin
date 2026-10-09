<?php

class SmsTemplateEngine
{
    public static function typeCatalog(): array
    {
        return [
            'booking_confirmation' => [
                'label' => 'Booking Confirmation',
                'entity_type' => 'booking',
                'variables' => ['BOOKING_ID', 'CHECKIN_DATE', 'CHECKOUT_DATE', 'TOTAL_AMOUNT', 'PAYMENT_STATUS', 'BOOKING_STATUS', 'ROOM_NAME', 'CUSTOMER_NAME', 'CUSTOMER_PHONE', 'HOTEL_NAME', 'HOTEL_PHONE', 'HOTEL_ADDRESS']
            ],
            'booking_pending' => [
                'label' => 'Booking Pending',
                'entity_type' => 'booking',
                'variables' => ['BOOKING_ID', 'CHECKIN_DATE', 'CHECKOUT_DATE', 'TOTAL_AMOUNT', 'PAYMENT_STATUS', 'BOOKING_STATUS', 'ROOM_NAME', 'CUSTOMER_NAME', 'CUSTOMER_PHONE', 'HOTEL_NAME', 'HOTEL_PHONE', 'HOTEL_ADDRESS']
            ],
            'booking_cancelled' => [
                'label' => 'Booking Cancelled',
                'entity_type' => 'booking',
                'variables' => ['BOOKING_ID', 'CHECKIN_DATE', 'CHECKOUT_DATE', 'TOTAL_AMOUNT', 'PAYMENT_STATUS', 'BOOKING_STATUS', 'ROOM_NAME', 'CUSTOMER_NAME', 'CUSTOMER_PHONE', 'HOTEL_NAME', 'HOTEL_PHONE', 'HOTEL_ADDRESS']
            ],
            'booking_checkin_reminder' => [
                'label' => 'Booking Check-in Reminder',
                'entity_type' => 'booking',
                'variables' => ['BOOKING_ID', 'CHECKIN_DATE', 'CHECKOUT_DATE', 'TOTAL_AMOUNT', 'PAYMENT_STATUS', 'BOOKING_STATUS', 'ROOM_NAME', 'CUSTOMER_NAME', 'CUSTOMER_PHONE', 'HOTEL_NAME', 'HOTEL_PHONE', 'HOTEL_ADDRESS']
            ],
            'booking_checkout_reminder' => [
                'label' => 'Booking Checkout Reminder',
                'entity_type' => 'booking',
                'variables' => ['BOOKING_ID', 'CHECKIN_DATE', 'CHECKOUT_DATE', 'TOTAL_AMOUNT', 'PAYMENT_STATUS', 'BOOKING_STATUS', 'ROOM_NAME', 'CUSTOMER_NAME', 'CUSTOMER_PHONE', 'HOTEL_NAME', 'HOTEL_PHONE', 'HOTEL_ADDRESS']
            ],
            'booking_completed' => [
                'label' => 'Booking Completed',
                'entity_type' => 'booking',
                'variables' => ['BOOKING_ID', 'CHECKIN_DATE', 'CHECKOUT_DATE', 'TOTAL_AMOUNT', 'PAYMENT_STATUS', 'BOOKING_STATUS', 'ROOM_NAME', 'CUSTOMER_NAME', 'CUSTOMER_PHONE', 'HOTEL_NAME', 'HOTEL_PHONE', 'HOTEL_ADDRESS']
            ],

            'payment_received' => [
                'label' => 'Payment Received',
                'entity_type' => 'payment',
                'variables' => ['PAYMENT_AMOUNT', 'PAYMENT_DATE', 'TRANSACTION_ID', 'BOOKING_ID', 'CUSTOMER_NAME', 'CUSTOMER_PHONE', 'HOTEL_NAME', 'HOTEL_PHONE']
            ],
            'payment_due' => [
                'label' => 'Payment Due',
                'entity_type' => 'payment',
                'variables' => ['PAYMENT_AMOUNT', 'PAYMENT_DATE', 'TRANSACTION_ID', 'BOOKING_ID', 'CUSTOMER_NAME', 'CUSTOMER_PHONE', 'HOTEL_NAME', 'HOTEL_PHONE']
            ],
            'payment_partial' => [
                'label' => 'Payment Partial',
                'entity_type' => 'payment',
                'variables' => ['PAYMENT_AMOUNT', 'PAYMENT_DATE', 'TRANSACTION_ID', 'BOOKING_ID', 'CUSTOMER_NAME', 'CUSTOMER_PHONE', 'HOTEL_NAME', 'HOTEL_PHONE']
            ],
            'payment_refund' => [
                'label' => 'Payment Refund',
                'entity_type' => 'payment',
                'variables' => ['PAYMENT_AMOUNT', 'PAYMENT_DATE', 'TRANSACTION_ID', 'BOOKING_ID', 'CUSTOMER_NAME', 'CUSTOMER_PHONE', 'HOTEL_NAME', 'HOTEL_PHONE']
            ],

            'restaurant_order_confirmed' => [
                'label' => 'Restaurant Order Confirmed',
                'entity_type' => 'restaurant_order',
                'variables' => ['ORDER_NO', 'ORDER_AMOUNT', 'ORDER_STATUS', 'TABLE_NAME', 'CUSTOMER_NAME', 'CUSTOMER_PHONE', 'HOTEL_NAME', 'HOTEL_PHONE']
            ],
            'restaurant_order_preparing' => [
                'label' => 'Restaurant Order Preparing',
                'entity_type' => 'restaurant_order',
                'variables' => ['ORDER_NO', 'ORDER_AMOUNT', 'ORDER_STATUS', 'TABLE_NAME', 'CUSTOMER_NAME', 'CUSTOMER_PHONE', 'HOTEL_NAME', 'HOTEL_PHONE']
            ],
            'restaurant_order_ready' => [
                'label' => 'Restaurant Order Ready',
                'entity_type' => 'restaurant_order',
                'variables' => ['ORDER_NO', 'ORDER_AMOUNT', 'ORDER_STATUS', 'TABLE_NAME', 'CUSTOMER_NAME', 'CUSTOMER_PHONE', 'HOTEL_NAME', 'HOTEL_PHONE']
            ],
            'restaurant_order_completed' => [
                'label' => 'Restaurant Order Completed',
                'entity_type' => 'restaurant_order',
                'variables' => ['ORDER_NO', 'ORDER_AMOUNT', 'ORDER_STATUS', 'TABLE_NAME', 'CUSTOMER_NAME', 'CUSTOMER_PHONE', 'HOTEL_NAME', 'HOTEL_PHONE']
            ],
            'restaurant_delivery_started' => [
                'label' => 'Restaurant Delivery Started',
                'entity_type' => 'restaurant_order',
                'variables' => ['ORDER_NO', 'ORDER_AMOUNT', 'ORDER_STATUS', 'TABLE_NAME', 'CUSTOMER_NAME', 'CUSTOMER_PHONE', 'HOTEL_NAME', 'HOTEL_PHONE']
            ],

            'welcome_sms' => [
                'label' => 'Welcome SMS',
                'entity_type' => 'customer',
                'variables' => ['CUSTOMER_NAME', 'CUSTOMER_PHONE', 'CUSTOMER_EMAIL', 'CUSTOMER_ADDRESS', 'HOTEL_NAME', 'HOTEL_PHONE']
            ],
            'birthday_sms' => [
                'label' => 'Birthday SMS',
                'entity_type' => 'customer',
                'variables' => ['CUSTOMER_NAME', 'CUSTOMER_PHONE', 'CUSTOMER_EMAIL', 'CUSTOMER_ADDRESS', 'HOTEL_NAME', 'HOTEL_PHONE']
            ],
            'promotional_sms' => [
                'label' => 'Promotional SMS',
                'entity_type' => 'customer',
                'variables' => ['CUSTOMER_NAME', 'CUSTOMER_PHONE', 'CUSTOMER_EMAIL', 'CUSTOMER_ADDRESS', 'HOTEL_NAME', 'HOTEL_PHONE']
            ],
            'membership_sms' => [
                'label' => 'Membership SMS',
                'entity_type' => 'customer',
                'variables' => ['CUSTOMER_NAME', 'CUSTOMER_PHONE', 'CUSTOMER_EMAIL', 'CUSTOMER_ADDRESS', 'HOTEL_NAME', 'HOTEL_PHONE']
            ],

            'staff_salary' => [
                'label' => 'Staff Salary',
                'entity_type' => 'user',
                'variables' => ['USER_NAME', 'USER_PHONE', 'USER_EMAIL', 'HOTEL_NAME', 'HOTEL_PHONE']
            ],
            'staff_attendance' => [
                'label' => 'Staff Attendance',
                'entity_type' => 'user',
                'variables' => ['USER_NAME', 'USER_PHONE', 'USER_EMAIL', 'HOTEL_NAME', 'HOTEL_PHONE']
            ],
            'staff_leave' => [
                'label' => 'Staff Leave',
                'entity_type' => 'user',
                'variables' => ['USER_NAME', 'USER_PHONE', 'USER_EMAIL', 'HOTEL_NAME', 'HOTEL_PHONE']
            ],
        ];
    }

    public static function typesList(): array
    {
        $rows = [];
        foreach (self::typeCatalog() as $slug => $info) {
            $rows[] = [
                'slug' => $slug,
                'label' => $info['label'],
                'entity_type' => $info['entity_type'],
                'variables' => $info['variables'],
            ];
        }
        return $rows;
    }

    public static function extractVariables(string $content): array
    {
        if ($content === '') {
            return [];
        }
        preg_match_all('/\{([A-Z0-9_]+)\}/', strtoupper($content), $matches);
        if (empty($matches[1])) {
            return [];
        }
        $vars = array_values(array_unique(array_map('trim', $matches[1])));
        sort($vars);
        return $vars;
    }

    public static function applyTemplate(string $message, array $vars): string
    {
        if ($message === '') {
            return '';
        }
        foreach ($vars as $key => $value) {
            $message = str_replace('{' . strtoupper((string)$key) . '}', (string)$value, $message);
        }
        return $message;
    }

    public static function systemVars(): array
    {
        return [
            'CURRENT_DATE' => date('Y-m-d'),
            'CURRENT_TIME' => date('H:i:s'),
            'CURRENT_YEAR' => date('Y'),
        ];
    }

    public static function resolveVariables(mysqli $conn, string $type, array $recipient, ?string $entityRef, array $manualVars = []): array
    {
        $catalog = self::typeCatalog();
        $meta = $catalog[$type] ?? ['entity_type' => 'customer', 'variables' => []];
        $entityType = (string)$meta['entity_type'];

        $resolved = self::systemVars();
        $resolved['CUSTOMER_NAME'] = (string)($recipient['name'] ?? 'Recipient');
        $resolved['CUSTOMER_PHONE'] = (string)($recipient['phone'] ?? '');
        $resolved['USER_NAME'] = (string)($recipient['name'] ?? 'User');
        $resolved['USER_PHONE'] = (string)($recipient['phone'] ?? '');

        if ($entityType === 'booking') {
            $resolved = array_merge($resolved, self::resolveBooking($conn, $entityRef, (string)($recipient['phone'] ?? '')));
        } elseif ($entityType === 'payment') {
            $resolved = array_merge($resolved, self::resolvePayment($conn, $entityRef, (string)($recipient['phone'] ?? '')));
        } elseif ($entityType === 'restaurant_order') {
            $resolved = array_merge($resolved, self::resolveRestaurantOrder($conn, $entityRef, (string)($recipient['phone'] ?? '')));
        } elseif ($entityType === 'customer') {
            $resolved = array_merge($resolved, self::resolveCustomer($conn, $entityRef, (string)($recipient['phone'] ?? '')));
        } elseif ($entityType === 'user') {
            $resolved = array_merge($resolved, self::resolveUser($conn, $entityRef, (string)($recipient['phone'] ?? '')));
        } elseif ($entityType === 'location') {
            $resolved = array_merge($resolved, self::resolveLocation($conn, $entityRef));
        }

        foreach ($manualVars as $k => $v) {
            $key = strtoupper(trim((string)$k));
            if ($key !== '') {
                $resolved[$key] = (string)$v;
            }
        }

        return $resolved;
    }

    private static function resolveBooking(mysqli $conn, ?string $entityRef, string $phone): array
    {
        $where = '';
        $types = '';
        $params = [];
        if ($entityRef !== null && trim($entityRef) !== '') {
            $ref = trim($entityRef);
            if (ctype_digit($ref)) {
                $where = 'b.id = ?';
                $types = 'i';
                $params[] = (int)$ref;
            } else {
                $where = 'b.booking_id = ?';
                $types = 's';
                $params[] = $ref;
            }
        } elseif ($phone !== '') {
            $where = 'c.phone = ?';
            $types = 's';
            $params[] = $phone;
        } else {
            return [];
        }

        $sql = "SELECT b.booking_id, b.checkin_date, b.checkout_date, b.total_amount, b.payment_status, b.booking_status,
                       c.name AS customer_name, c.phone AS customer_phone, c.email AS customer_email, c.address AS customer_address,
                       r.name AS room_name,
                       COALESCE(sb.website_name, l.name, '') AS hotel_name,
                       COALESCE(sb.phone, l.phone, '') AS hotel_phone,
                       COALESCE(sb.address, l.address, '') AS hotel_address
                FROM bookings b
                JOIN customers c ON c.id = b.customer_id
                LEFT JOIN rooms r ON r.id = b.room_id
                LEFT JOIN locations l ON l.id = b.location_id
                LEFT JOIN settings_basic sb ON sb.location_id = b.location_id
                WHERE {$where}
                ORDER BY b.id DESC
                LIMIT 1";
        $row = self::queryOne($conn, $sql, $types, $params);
        if (!$row) {
            return [];
        }

        return [
            'BOOKING_ID' => (string)($row['booking_id'] ?? ''),
            'CHECKIN_DATE' => (string)($row['checkin_date'] ?? ''),
            'CHECKOUT_DATE' => (string)($row['checkout_date'] ?? ''),
            'TOTAL_AMOUNT' => (string)($row['total_amount'] ?? ''),
            'PAYMENT_STATUS' => (string)($row['payment_status'] ?? ''),
            'BOOKING_STATUS' => (string)($row['booking_status'] ?? ''),
            'ROOM_NAME' => (string)($row['room_name'] ?? ''),
            'CUSTOMER_NAME' => (string)($row['customer_name'] ?? ''),
            'CUSTOMER_PHONE' => (string)($row['customer_phone'] ?? ''),
            'CUSTOMER_EMAIL' => (string)($row['customer_email'] ?? ''),
            'CUSTOMER_ADDRESS' => (string)($row['customer_address'] ?? ''),
            'HOTEL_NAME' => (string)($row['hotel_name'] ?? ''),
            'HOTEL_PHONE' => (string)($row['hotel_phone'] ?? ''),
            'HOTEL_ADDRESS' => (string)($row['hotel_address'] ?? ''),
        ];
    }

    private static function resolvePayment(mysqli $conn, ?string $entityRef, string $phone): array
    {
        $where = '';
        $types = '';
        $params = [];
        if ($entityRef !== null && trim($entityRef) !== '') {
            $ref = trim($entityRef);
            if (ctype_digit($ref)) {
                $where = 'p.id = ?';
                $types = 'i';
                $params[] = (int)$ref;
            } else {
                $where = 'p.transaction_id = ?';
                $types = 's';
                $params[] = $ref;
            }
        } elseif ($phone !== '') {
            $where = 'c.phone = ?';
            $types = 's';
            $params[] = $phone;
        } else {
            return [];
        }

        $sql = "SELECT p.amount, p.payment_date, p.transaction_id,
                       b.booking_id,
                       c.name AS customer_name, c.phone AS customer_phone, c.email AS customer_email, c.address AS customer_address,
                       COALESCE(sb.website_name, l.name, '') AS hotel_name,
                       COALESCE(sb.phone, l.phone, '') AS hotel_phone
                FROM payments p
                JOIN bookings b ON b.id = p.booking_id
                JOIN customers c ON c.id = b.customer_id
                LEFT JOIN locations l ON l.id = b.location_id
                LEFT JOIN settings_basic sb ON sb.location_id = b.location_id
                WHERE {$where}
                ORDER BY p.id DESC
                LIMIT 1";
        $row = self::queryOne($conn, $sql, $types, $params);
        if (!$row) {
            return [];
        }

        return [
            'PAYMENT_AMOUNT' => (string)($row['amount'] ?? ''),
            'PAYMENT_DATE' => (string)($row['payment_date'] ?? ''),
            'TRANSACTION_ID' => (string)($row['transaction_id'] ?? ''),
            'BOOKING_ID' => (string)($row['booking_id'] ?? ''),
            'CUSTOMER_NAME' => (string)($row['customer_name'] ?? ''),
            'CUSTOMER_PHONE' => (string)($row['customer_phone'] ?? ''),
            'CUSTOMER_EMAIL' => (string)($row['customer_email'] ?? ''),
            'CUSTOMER_ADDRESS' => (string)($row['customer_address'] ?? ''),
            'HOTEL_NAME' => (string)($row['hotel_name'] ?? ''),
            'HOTEL_PHONE' => (string)($row['hotel_phone'] ?? ''),
        ];
    }

    private static function resolveRestaurantOrder(mysqli $conn, ?string $entityRef, string $phone): array
    {
        $where = '';
        $types = '';
        $params = [];
        if ($entityRef !== null && trim($entityRef) !== '') {
            $ref = trim($entityRef);
            if (ctype_digit($ref)) {
                $where = 'o.id = ?';
                $types = 'i';
                $params[] = (int)$ref;
            } else {
                $where = 'o.order_no = ?';
                $types = 's';
                $params[] = $ref;
            }
        } elseif ($phone !== '') {
            $where = 'o.customer_phone = ?';
            $types = 's';
            $params[] = $phone;
        } else {
            return [];
        }

        $sql = "SELECT o.order_no, o.final_amount, o.order_status, o.customer_name, o.customer_phone,
                       t.name AS table_name,
                       COALESCE(sb.website_name, l.name, '') AS hotel_name,
                       COALESCE(sb.phone, l.phone, '') AS hotel_phone
                FROM restaurant_orders o
                LEFT JOIN restaurant_tables t ON t.id = o.table_id
                LEFT JOIN locations l ON l.id = o.location_id
                LEFT JOIN settings_basic sb ON sb.location_id = o.location_id
                WHERE {$where}
                ORDER BY o.id DESC
                LIMIT 1";
        $row = self::queryOne($conn, $sql, $types, $params);
        if (!$row) {
            return [];
        }

        return [
            'ORDER_NO' => (string)($row['order_no'] ?? ''),
            'ORDER_AMOUNT' => (string)($row['final_amount'] ?? ''),
            'ORDER_STATUS' => (string)($row['order_status'] ?? ''),
            'TABLE_NAME' => (string)($row['table_name'] ?? ''),
            'CUSTOMER_NAME' => (string)($row['customer_name'] ?? ''),
            'CUSTOMER_PHONE' => (string)($row['customer_phone'] ?? ''),
            'HOTEL_NAME' => (string)($row['hotel_name'] ?? ''),
            'HOTEL_PHONE' => (string)($row['hotel_phone'] ?? ''),
        ];
    }

    private static function resolveCustomer(mysqli $conn, ?string $entityRef, string $phone): array
    {
        $where = '';
        $types = '';
        $params = [];
        if ($entityRef !== null && trim($entityRef) !== '') {
            $ref = trim($entityRef);
            if (ctype_digit($ref)) {
                $where = 'c.id = ?';
                $types = 'i';
                $params[] = (int)$ref;
            } else {
                $where = 'c.phone = ?';
                $types = 's';
                $params[] = $ref;
            }
        } elseif ($phone !== '') {
            $where = 'c.phone = ?';
            $types = 's';
            $params[] = $phone;
        } else {
            return [];
        }

        $sql = "SELECT c.name, c.phone, c.email, c.address,
                       COALESCE(sb.website_name, l.name, '') AS hotel_name,
                       COALESCE(sb.phone, l.phone, '') AS hotel_phone
                FROM customers c
                LEFT JOIN locations l ON l.id = c.location_id
                LEFT JOIN settings_basic sb ON sb.location_id = c.location_id
                WHERE {$where}
                ORDER BY c.id DESC
                LIMIT 1";
        $row = self::queryOne($conn, $sql, $types, $params);
        if (!$row) {
            return [];
        }

        return [
            'CUSTOMER_NAME' => (string)($row['name'] ?? ''),
            'CUSTOMER_PHONE' => (string)($row['phone'] ?? ''),
            'CUSTOMER_EMAIL' => (string)($row['email'] ?? ''),
            'CUSTOMER_ADDRESS' => (string)($row['address'] ?? ''),
            'HOTEL_NAME' => (string)($row['hotel_name'] ?? ''),
            'HOTEL_PHONE' => (string)($row['hotel_phone'] ?? ''),
        ];
    }

    private static function resolveUser(mysqli $conn, ?string $entityRef, string $phone): array
    {
        $where = '';
        $types = '';
        $params = [];
        if ($entityRef !== null && trim($entityRef) !== '') {
            $ref = trim($entityRef);
            if (ctype_digit($ref)) {
                $where = 'u.id = ?';
                $types = 'i';
                $params[] = (int)$ref;
            } else {
                $where = 'u.mobile = ?';
                $types = 's';
                $params[] = $ref;
            }
        } elseif ($phone !== '') {
            $where = 'u.mobile = ?';
            $types = 's';
            $params[] = $phone;
        } else {
            return [];
        }

        $sql = "SELECT u.name, u.mobile, '' AS user_email,
                       COALESCE(sb.website_name, l.name, '') AS hotel_name,
                       COALESCE(sb.phone, l.phone, '') AS hotel_phone
                FROM users u
                LEFT JOIN locations l ON l.id = u.location_id
                LEFT JOIN settings_basic sb ON sb.location_id = u.location_id
                WHERE {$where}
                ORDER BY u.id DESC
                LIMIT 1";
        $row = self::queryOne($conn, $sql, $types, $params);
        if (!$row) {
            return [];
        }

        return [
            'USER_NAME' => (string)($row['name'] ?? ''),
            'USER_PHONE' => (string)($row['mobile'] ?? ''),
            'USER_EMAIL' => (string)($row['user_email'] ?? ''),
            'HOTEL_NAME' => (string)($row['hotel_name'] ?? ''),
            'HOTEL_PHONE' => (string)($row['hotel_phone'] ?? ''),
        ];
    }

    private static function resolveLocation(mysqli $conn, ?string $entityRef): array
    {
        $where = '1=1';
        $types = '';
        $params = [];
        if ($entityRef !== null && trim($entityRef) !== '') {
            $ref = trim($entityRef);
            if (ctype_digit($ref)) {
                $where = 'l.id = ?';
                $types = 'i';
                $params[] = (int)$ref;
            } else {
                $where = 'l.name = ?';
                $types = 's';
                $params[] = $ref;
            }
        }

        $sql = "SELECT l.name, l.phone, l.address,
                       COALESCE(sb.website_name, l.name, '') AS hotel_name,
                       COALESCE(sb.phone, l.phone, '') AS hotel_phone,
                       COALESCE(sb.address, l.address, '') AS hotel_address
                FROM locations l
                LEFT JOIN settings_basic sb ON sb.location_id = l.id
                WHERE {$where}
                ORDER BY l.id ASC
                LIMIT 1";
        $row = self::queryOne($conn, $sql, $types, $params);
        if (!$row) {
            return [];
        }

        return [
            'HOTEL_NAME' => (string)($row['hotel_name'] ?? ''),
            'HOTEL_PHONE' => (string)($row['hotel_phone'] ?? ''),
            'HOTEL_ADDRESS' => (string)($row['hotel_address'] ?? ''),
        ];
    }

    private static function queryOne(mysqli $conn, string $sql, string $types = '', array $params = []): ?array
    {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return null;
        }

        if ($types !== '' && !empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        if (!$stmt->execute()) {
            $stmt->close();
            return null;
        }

        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        if ($result instanceof mysqli_result) {
            $result->free();
        }
        $stmt->close();
        return $row ?: null;
    }
}
