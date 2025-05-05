<?php

namespace GooogleChat\GoogleChatNotifications;

use Illuminate\Support\Facades\Http;

class GoogleChatNotifier
{
    protected $webhookUrl;

    public function __construct($webhookUrl)
    {
        $this->webhookUrl = $webhookUrl;
    }

    public function info($message, $details = null)
    {
        return $this->send('INFO', $message, $details, '#0088ff');
    }

    public function warning($message, $details = null)
    {
        return $this->send('WARNING', $message, $details, '#ffcc00');
    }

    public function error($message, $details = null)
    {
        // If message is a Throwable, format it accordingly
        if ($message instanceof \Throwable) {
            $exception = $message;
            $details = $details ?? [];
            
            // If details is already an array, merge with exception details
            if (!is_array($details)) {
                $details = ['additional_info' => $details];
            }
            
            // Add exception details
            $details = array_merge($details, [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $this->formatTrace($exception)
            ]);
            
            return $this->send('ERROR', 'Exception: ' . $exception->getMessage(), $details, '#ff0000');
        }
        
        return $this->send('ERROR', $message, $details, '#ff0000');
    }
    
    /**
     * Format the exception trace to be more readable
     *
     * @param \Throwable $exception
     * @return array
     */
    protected function formatTrace(\Throwable $exception)
    {
        $traceItems = [];
        $trace = $exception->getTrace();
        
        // Only include the first 5 items to avoid very long messages
        $trace = array_slice($trace, 0, 5);
        
        foreach ($trace as $item) {
            $file = $item['file'] ?? '[internal function]';
            $line = $item['line'] ?? '';
            $class = $item['class'] ?? '';
            $type = $item['type'] ?? '';
            $function = $item['function'] ?? '';
            
            $traceItems[] = "$file:$line - $class$type$function()";
        }
        
        return $traceItems;
    }

    public function send($level, $message, $details = null, $color = '#000000')
    {
        $data = [
            'cards' => [
                [
                    'header' => [
                        'title' => 'Inventory Notification',
                        'subtitle' => $level,
                        'imageUrl' => 'https://www.gstatic.com/images/branding/product/2x/chat_48dp.png',
                        'imageStyle' => 'AVATAR'
                    ],
                    'sections' => [
                        [
                            'widgets' => [
                                [
                                    'textParagraph' => [
                                        'text' => $message
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        if ($details) {
            $data['cards'][0]['sections'][] = [
                'widgets' => [
                    [
                        'keyValue' => [
                            'topLabel' => 'Details',
                            'content' => is_array($details) ? json_encode($details) : $details
                        ]
                    ]
                ]
            ];
        }

        // Add timestamp
        $data['cards'][0]['sections'][] = [
            'widgets' => [
                [
                    'textParagraph' => [
                        'text' => 'Time: ' . date('Y-m-d H:i:s')
                    ]
                ]
            ]
        ];

        return Http::post($this->webhookUrl, $data);
    }
}