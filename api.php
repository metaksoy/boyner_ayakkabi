<?php
header('Content-Type: application/json; charset=utf-8');

class BoynerAPI {
    private $baseUrl = 'https://mpcore-listingsearch-prod.boyner.com.tr/product/search';
    private $headers = [
        'accept: */*',
        'accept-language: tr-TR,tr;q=0.7',
        'appversion: 0.1.0',
        'ismarketplace: true',
        'platform: 1',
        'storeid: 1',
        'x-is-web: true'
    ];
    
    public function getAllProducts($maxPages = 10, $pageSize = 24) {
        $logFile = __DIR__ . '/api_debug.log';
        file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Starting to fetch all products, max pages: {$maxPages}\n", FILE_APPEND);
        
        $allProducts = [];
        $totalProducts = 0;
        $currentPage = 1;
        $hasMorePages = true;
        
        while ($hasMorePages && $currentPage <= $maxPages) {
            // Construct URL for current page
            $url = "https://mpcore-listingsearch-prod.boyner.com.tr/product/search?Page={$currentPage}&DropListingPageSize={$pageSize}&LastSelectedAttributeId=2&FilterIDList=8770&CategoryID=574";
            
            file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Fetching page {$currentPage}: {$url}\n", FILE_APPEND);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if(curl_errno($ch) || $httpCode != 200) {
                file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Error fetching page {$currentPage}: HTTP {$httpCode}\n", FILE_APPEND);
                curl_close($ch);
                break; // Stop on error
            }
            
            curl_close($ch);
            
            // Parse the response
            $data = json_decode($response, true);
            
            if (!$data || !isset($data['Products']) || !is_array($data['Products'])) {
                file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Invalid response structure for page {$currentPage}\n", FILE_APPEND);
                break; // Stop on invalid response
            }
            
            // Process products from this page
            $pageProducts = [];
            foreach ($data['Products'] as $product) {
                try {
                    // Check if the product has the minimum required fields
                    if(!isset($product['ID']) || !isset($product['DisplayName'])) {
                        continue;
                    }
                    
                    $pageProducts[] = [
                        'id' => $product['ID'] ?? 0,
                        'name' => $product['DisplayName'] ?? 'Unknown Product',
                        'brand' => $product['BrandName'] ?? 'Unknown Brand',
                        'price' => [
                            'current' => $product['ActualPriceToShowOnScreen'] ?? 0,
                            'formatted' => $product['ActualPriceToShowOnScreenFormatted'] ?? '0 TL',
                            'discount_rate' => $product['DiscountRate'] ?? 0
                        ],
                        'stock' => [
                            'available' => $product['HasStock'] ?? false,
                            'count' => $product['StockCount'] ?? 0
                        ],
                        'images' => [
                            'main' => $product['FirstProductImageUrl'] ?? '',
                            'all' => isset($product['Medias']) && is_array($product['Medias']) ? 
                                array_map(function($media) {
                                    return $media['Path'] ?? '';
                                }, $product['Medias']) : []
                        ],
                        'url' => $product['FriendlyURI'] ?? '',
                        'badges' => isset($product['Badges']) && is_array($product['Badges']) ? 
                            array_map(function($badge) {
                                return $badge['BadgeName'] ?? '';
                            }, $product['Badges']) : []
                    ];
                } catch (Exception $e) {
                    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Error processing product on page {$currentPage}: {$e->getMessage()}\n", FILE_APPEND);
                    continue;
                }
            }
            
            // Add products from this page to the total collection
            $allProducts = array_merge($allProducts, $pageProducts);
            
            // Update total count if available
            if (isset($data['TotalCount'])) {
                $totalProducts = $data['TotalCount'];
            }
            
            // Check if we've reached the last page
            $productsPerPage = $pageSize;
            $totalPages = ceil($totalProducts / $productsPerPage);
            
            file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Page {$currentPage} fetched: " . count($pageProducts) . " products, total so far: " . count($allProducts) . "\n", FILE_APPEND);
            
            // If we got fewer products than expected or reached total pages, we're done
            if (count($pageProducts) < $pageSize || $currentPage >= $totalPages) {
                $hasMorePages = false;
            }
            
            $currentPage++;
        }
        
        file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Completed fetching all products. Total: " . count($allProducts) . "\n", FILE_APPEND);
        
        return [
            'error' => false,
            'total_products' => $totalProducts,
            'pages_fetched' => $currentPage - 1,
            'total_pages' => ceil($totalProducts / $pageSize),
            'products' => $allProducts
        ];
    }

    public function getProducts($page = 3, $pageSize = 24) {
        $logFile = __DIR__ . '/api_debug.log';
        
        // Use the specific URL as requested
        $url = 'https://mpcore-listingsearch-prod.boyner.com.tr/product/search?Page=3&DropListingPageSize=24&LastSelectedAttributeId=2&FilterIDList=8770&CategoryID=574';
        
        file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Requesting URL: {$url}\n", FILE_APPEND);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Set timeout to 30 seconds
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'); // Set a user agent
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification for testing
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        
        file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "HTTP Code: {$httpCode}, Effective URL: {$effectiveUrl}\n", FILE_APPEND);
        
        if(curl_errno($ch)) {
            $error = curl_error($ch);
            file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "cURL Error: {$error}\n", FILE_APPEND);
            curl_close($ch);
            
            return [
                'error' => true,
                'message' => 'cURL Error: ' . $error
            ];
        }
        
        if($httpCode != 200) {
            file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "HTTP Error: {$httpCode}\n", FILE_APPEND);
            curl_close($ch);
            
            return [
                'error' => true,
                'message' => 'HTTP Error: ' . $httpCode
            ];
        }
        
        curl_close($ch);
        
        // Save a sample of the response for debugging
        $responsePreview = substr($response, 0, 1000) . (strlen($response) > 1000 ? '...' : '');
        file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Response preview: {$responsePreview}\n", FILE_APPEND);
        
        return $this->parseResponse($response);
    }
    
    // No fallback request needed as we're using a specific URL

    private function parseResponse($response) {
        $logFile = __DIR__ . '/api_debug.log';
        
        // Check if response is empty
        if(empty($response)) {
            file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Empty response received\n", FILE_APPEND);
            return [
                'error' => true,
                'message' => 'Empty response received from API'
            ];
        }
        
        // Try to decode JSON and capture any errors
        $data = json_decode($response, true);
        $jsonError = json_last_error();
        
        if($jsonError !== JSON_ERROR_NONE) {
            // Log the first part of the response for debugging
            $responsePreview = substr($response, 0, 500) . (strlen($response) > 500 ? '...' : '');
            file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "JSON Error: " . json_last_error_msg() . "\nResponse: {$responsePreview}\n", FILE_APPEND);
            
            // Check if the response might be HTML instead of JSON (common when API returns an error page)
            if (strpos($response, '<!DOCTYPE html>') !== false || strpos($response, '<html') !== false) {
                return [
                    'error' => true,
                    'message' => 'API returned HTML instead of JSON. The service might be temporarily unavailable.'
                ];
            }
            
            return [
                'error' => true,
                'message' => 'Invalid JSON response: ' . json_last_error_msg()
            ];
        }
        
        // Log the structure of the response for debugging
        $availableKeys = is_array($data) ? implode(', ', array_keys($data)) : 'none';
        file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "API response structure. Available keys: {$availableKeys}\n", FILE_APPEND);
        
        // Check if the expected data structure exists
        if(!isset($data['Products']) || !is_array($data['Products'])) {
            file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Invalid API structure. Available keys: {$availableKeys}\n", FILE_APPEND);
            
            // If we have a different structure, try to adapt
            if(isset($data['products']) && is_array($data['products'])) {
                // Some APIs use lowercase 'products' instead
                $data['Products'] = $data['products'];
            } else {
                return [
                    'error' => true,
                    'message' => 'Invalid API response structure: Products array not found'
                ];
            }
        }
        
        if(empty($data['Products'])) {
            file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "No products found in response\n", FILE_APPEND);
            return [
                'error' => false,
                'total_products' => 0,
                'page' => 3, // Fixed to page 3 as per the URL
                'products' => []
            ];
        }

        $products = [];
        $processedCount = 0;
        $errorCount = 0;
        
        foreach($data['Products'] as $product) {
            try {
                // Check if the product has the minimum required fields
                if(!isset($product['ID']) || !isset($product['DisplayName'])) {
                    $errorCount++;
                    continue;
                }
                
                $products[] = [
                    'id' => $product['ID'] ?? 0,
                    'name' => $product['DisplayName'] ?? 'Unknown Product',
                    'brand' => $product['BrandName'] ?? 'Unknown Brand',
                    'price' => [
                        'current' => $product['ActualPriceToShowOnScreen'] ?? 0,
                        'formatted' => $product['ActualPriceToShowOnScreenFormatted'] ?? '0 TL',
                        'discount_rate' => $product['DiscountRate'] ?? 0
                    ],
                    'stock' => [
                        'available' => $product['HasStock'] ?? false,
                        'count' => $product['StockCount'] ?? 0
                    ],
                    'images' => [
                        'main' => $product['FirstProductImageUrl'] ?? '',
                        'all' => isset($product['Medias']) && is_array($product['Medias']) ? 
                            array_map(function($media) {
                                return $media['Path'] ?? '';
                            }, $product['Medias']) : []
                    ],
                    'url' => $product['FriendlyURI'] ?? '',
                    'badges' => isset($product['Badges']) && is_array($product['Badges']) ? 
                        array_map(function($badge) {
                            return $badge['BadgeName'] ?? '';
                        }, $product['Badges']) : []
                ];
                $processedCount++;
            } catch (Exception $e) {
                // Log the error and skip this product
                file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Error processing product: {$e->getMessage()}\n", FILE_APPEND);
                $errorCount++;
                continue;
            }
        }
        
        file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Processed {$processedCount} products with {$errorCount} errors\n", FILE_APPEND);
        
        // If we couldn't process any products but there were some in the response, there's a structural problem
        if($processedCount === 0 && count($data['Products']) > 0) {
            // Log a sample product for debugging
            $sampleProduct = json_encode($data['Products'][0]);
            file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Sample product structure: {$sampleProduct}\n", FILE_APPEND);
            
            return [
                'error' => true,
                'message' => 'Could not process any products due to unexpected data structure'
            ];
        }

        return [
            'error' => false,
            'total_products' => $data['TotalCount'] ?? count($products),
            'page' => 3, // Fixed to page 3 as per the URL
            'products' => $products
        ];

                  return $badge['BadgeName'] ?? '';
                        }, $product['Badges']) : []
                ];
                $processedCount++;
            } catch (Exception $e) {
                // Log the error and skip this product
                file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Error processing product: {$e->getMessage()}\n", FILE_APPEND);
                $errorCount++;
                continue;
            }
        }
        
        file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Processed {$processedCount} products with {$errorCount} errors\n", FILE_APPEND);
        
        // If we couldn't process any products but there were some in the response, there's a structural problem
        if($processedCount === 0 && count($data['Products']) > 0) {
            // Log a sample product for debugging
            $sampleProduct = json_encode($data['Products'][0]);
            file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Sample product structure: {$sampleProduct}\n", FILE_APPEND);
            
            return [
                'error' => true,
                'message' => 'Could not process any products due to unexpected data structure'
            ];
        }

        return [
            'error' => false,
        return [
            'error' => false,
            'total_products' => $data['TotalCount'] ?? count($products),
            'page' => 3, // Fixed to page 3 as per the URL
            'products' => $products

    // Since we're only fetching from a specific URL, we don't need the getAllProducts method
    public function getAllProducts($maxPages = 1) {
        // Just return the results from the specific URL
        return $this->getProducts();
    }
}

// API Endpoint
try {
    // Enable error logging
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    
    // Create a log file for debugging
    $logFile = __DIR__ . '/api_debug.log';
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "API request received\n", FILE_APPEND);
    
    $api = new BoynerAPI();
    
    // Always fetch from the specific URL
    $result = $api->getProducts();
    
    // Log the result status
    $status = isset($result['error']) && $result['error'] ? 'ERROR' : 'SUCCESS';
    $message = isset($result['message']) ? $result['message'] : 'No message';
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Result: {$status}, Message: {$message}\n", FILE_APPEND);
    
} catch (Exception $e) {
    // Catch any unexpected exceptions
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Exception: {$e->getMessage()}\n", FILE_APPEND);
    $result = [
        'error' => true,
        'message' => 'Server error: ' . $e->getMessage()
    ];
}

// Set appropriate HTTP status code based on error
if (isset($result['error']) && $result['error']) {
    // Don't send 500 status code as it can cause the browser to show its own error page
    // Instead, send 200 OK but include the error in the JSON response
    // This allows our frontend to handle the error gracefully
    http_response_code(200);
    
    // Log the error
    $logFile = __DIR__ . '/api_debug.log';
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Sending error response: {$result['message']}\n", FILE_APPEND);
}

// Output the JSON response
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>