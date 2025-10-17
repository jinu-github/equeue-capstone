<?php
class SmsService {
    private $email;
    private $password;
    private $apicode;
    private $sender_name;
    private $clinic_name;

    public function __construct() {
        $this->email = SMS_EMAIL;
        $this->password = SMS_PASSWORD;
        $this->apicode = SMS_APICODE;
        $this->sender_name = SMS_SENDER_NAME;
        $this->clinic_name = CLINIC_NAME;
    }

    public function send_sms($to, $message) {
        try {
            $url = SMS_BASE_URL;

            // Remove + from phone number if present
            $recipient = str_replace('+', '', $to);

            $itexmo = array(
                'Email' => $this->email,
                'Password' => $this->password,
                'ApiCode' => $this->apicode,
                'Recipients' => json_encode([$recipient]),
                'Message' => $message,
            );

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($itexmo));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($result === false || $httpCode !== 200) {
                error_log("SMS sending failed for $to - HTTP Code: $httpCode");
                return false;
            }

            // iTexMo returns a string like "0|Message sent" or error code
            if (strpos($result, '0|') === 0) {
                return $result;
            }

            error_log("SMS failed: " . $result);
            return false;

        } catch (Exception $e) {
            error_log("SMS Error: " . $e->getMessage());
            return false;
        }
    }

    // Queue Status Updates
    public function send_queue_status_update($patient_name, $contact_number, $queue_number, $patients_ahead) {
        if ($patients_ahead == 0) {
            $message = "Update: You're next in line at {$this->clinic_name}. Please prepare to proceed to the waiting area.";
        } elseif ($patients_ahead == 1) {
            $message = "Your turn is coming soon! {$patients_ahead} patient ahead. Please be ready.";
        } else {
            $message = "Update: There are {$patients_ahead} patients ahead of you. Please prepare to proceed to the waiting area.";
        }

        return $this->send_sms($contact_number, $message);
    }

    // When It's Their Turn
    public function send_your_turn_notification($patient_name, $contact_number, $queue_number, $room_number = null) {
        if ($room_number) {
            $message = "It's your turn now. Please proceed to Room {$room_number} for your consultation.";
        } else {
            $message = "It's your turn now. Please proceed to the consultation room.";
        }

        return $this->send_sms($contact_number, $message);
    }

    // Missed Turn / Requeue
    public function send_missed_turn_notification($patient_name, $contact_number, $queue_number) {
        $message = "You missed your turn for Queue No. {$queue_number}. Please check in at reception to be re-queued.";

        return $this->send_sms($contact_number, $message);
    }

    public function send_called_but_not_present($patient_name, $contact_number, $queue_number) {
        $message = "We called your number (Queue No. {$queue_number}) but you weren't present. Please approach the counter.";

        return $this->send_sms($contact_number, $message);
    }

    // Appointment Reminders
    public function send_appointment_reminder($patient_name, $contact_number, $doctor_name, $appointment_date, $appointment_time) {
        $message = "Reminder: You have an appointment at {$this->clinic_name} on {$appointment_date} at {$appointment_time}";

        return $this->send_sms($contact_number, $message);
    }

    public function send_consultation_reminder($patient_name, $contact_number, $doctor_name, $appointment_time) {
        $message = "Your consultation with Dr. {$doctor_name} is scheduled for {$appointment_time} today.";

        return $this->send_sms($contact_number, $message);
    }

    // Welcome message when patient registers
    public function send_welcome_message($patient_name, $contact_number, $queue_number, $department_name) {
        $message = "Hello {$patient_name}, your queue number is {$queue_number} in {$department_name}. We'll send you updates about your position.";

        return $this->send_sms($contact_number, $message);
    }

    // Test SMS functionality
    public function test_sms($test_number) {
        $message = "Test SMS from {$this->clinic_name} system. If you received this, SMS is working!";
        return $this->send_sms($test_number, $message);
    }
}
?>