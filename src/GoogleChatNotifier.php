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
        // Use Cards V2 format for better display options
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
                            'imageAltText' => strtoupper($level)
                        ],
                        'sections' => [
                            [
                                'widgets' => [
                                    [
                                        'decoratedText' => [
                                            'text' => $message,
                                            'wrapText' => true
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        // Set color based on level
        $iconColor = $color;
        
        // Format details to avoid truncation
        if ($details) {
            $detailsSection = [
                'header' => 'Details',
                'collapsible' => false,
                'widgets' => []
            ];
            
            if (is_array($details)) {
                // Process each detail as a separate decorated text widget
                foreach ($details as $key => $value) {
                    $formattedKey = ucfirst(str_replace('_', ' ', $key));
                    
                    // Format arrays and objects properly
                    if (is_array($value) || is_object($value)) {
                        $formattedValue = json_encode($value, JSON_PRETTY_PRINT);
                        
                        $detailsSection['widgets'][] = [
                            'decoratedText' => [
                                'topLabel' => $formattedKey,
                                'text' => $formattedValue,
                                'wrapText' => true
                            ]
                        ];
                    } else {
                        $detailsSection['widgets'][] = [
                            'decoratedText' => [
                                'topLabel' => $formattedKey,
                                'text' => (string)$value,
                                'wrapText' => true
                            ]
                        ];
                    }
                }
            } else {
                // If not an array, just add as a single entry
                $detailsSection['widgets'][] = [
                    'decoratedText' => [
                        'text' => (string)$details,
                        'wrapText' => true
                    ]
                ];
            }
            
            // Add the details section to the card
            $data['cardsV2'][0]['card']['sections'][] = $detailsSection;
        }

        // Add timestamp
        $data['cardsV2'][0]['card']['sections'][] = [
            'widgets' => [
                [
                    'decoratedText' => [
                        'text' => 'Time: ' . date('Y-m-d H:i:s'),
                        'wrapText' => true
                    ]
                ]
            ]
        ];

        return Http::post($this->webhookUrl, $data);
    }
}