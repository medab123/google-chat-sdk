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
        // Get icon based on level
        $icon = $this->getLevelIcon($level);
        
        // Format message with colored heading based on level
        $formattedMessage = "<b><font color=\"$color\">$icon $message</font></b>";
        
        // Cards v2 format
        $data = [
            'cardsV2' => [
                [
                    'cardId' => 'inventory-notification-' . uniqid(),
                    'card' => [
                        'header' => [
                            'title' => 'Inventory Notification',
                            'subtitle' => $level,
                            'imageUrl' => 'https://www.gstatic.com/images/branding/product/2x/chat_48dp.png',
                            'imageType' => 'CIRCLE',
                            'imageAltText' => 'Notification'
                        ],
                        'sections' => [
                            [
                                'widgets' => [
                                    [
                                        'textParagraph' => [
                                            'text' => $formattedMessage
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        // Add details section if provided
        if ($details) {
            $detailsSection = [
                'header' => 'Details',
                'collapsible' => true,
                'widgets' => [
                    [
                        'textParagraph' => [
                            'text' => is_array($details) 
                                ? '<pre>' . json_encode($details, JSON_PRETTY_PRINT) . '</pre>' 
                                : $details
                        ]
                    ]
                ]
            ];
            
            $data['cardsV2'][0]['card']['sections'][] = $detailsSection;
        }

        // Add timestamp section
        $data['cardsV2'][0]['card']['sections'][] = [
            'widgets' => [
                [
                    'decoratedText' => [
                        'startIcon' => [
                            'knownIcon' => 'CLOCK'
                        ],
                        'text' => date('Y-m-d H:i:s')
                    ]
                ]
            ]
        ];

        return Http::post($this->webhookUrl, $data);
    }
    
    /**
     * Get appropriate icon for notification level
     *
     * @param string $level
     * @return string
     */
    protected function getLevelIcon($level)
    {
        switch (strtoupper($level)) {
            case 'INFO':
                return '🔵';
            case 'WARNING':
                return '⚠️';
            case 'ERROR':
                return '❌';
            default:
                return '📢';
        }
    }

}
