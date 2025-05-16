const fetch = require('node-fetch');

exports.handler = async function(event, context) {
    const { Page, DropListingPageSize } = event.queryStringParameters;

    const defaultPage = 1;
    const defaultPageSize = 24;

    const page = Page || defaultPage;
    const pageSize = DropListingPageSize || defaultPageSize;

    // Orijinal API URL'i
    // URL'deki CategoryID=574 ve FilterIDList=8770 (spor ayakkabı kategorisi için) sabit kalacak gibi görünüyor.
    // LastSelectedAttributeId=2 de genel bir filtreleme parametresi olabilir.
    const apiUrl = `https://mpcore-listingsearch-prod.boyner.com.tr/product/search?Page=${page}&DropListingPageSize=${pageSize}&LastSelectedAttributeId=2&FilterIDList=8770&CategoryID=574`;

    const headers = {
        'accept': '*/*',
        'accept-language': 'tr-TR,tr;q=0.7',
        'appversion': '0.1.0',
        'ismarketplace': 'true',
        'platform': '1',
        'storeid': '1',
        'x-is-web': 'true',
        // Tarayıcıların gönderdiği tipik bir User-Agent
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
    };

    try {
        console.log(`Netlify Function: Fetching from ${apiUrl}`);
        const response = await fetch(apiUrl, { 
            method: 'GET',
            headers: headers
        });

        if (!response.ok) {
            const errorText = await response.text();
            console.error(`Netlify Function: API error ${response.status}: ${errorText}`);
            return {
                statusCode: response.status,
                body: JSON.stringify({ 
                    error: true, 
                    message: `API error: ${response.status} ${response.statusText}`, 
                    details: errorText 
                }),
                headers: { 'Content-Type': 'application/json' }
            };
        }

        const data = await response.json();
        console.log('Netlify Function: Data received from API:', data);

        // İstemcinin beklediği formatta yanıt döndür
        return {
            statusCode: 200,
            body: JSON.stringify(data), // Orijinal API yanıtını doğrudan döndür
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        };

    } catch (error) {
        console.error('Netlify Function: Error fetching products:', error);
        return {
            statusCode: 500,
            body: JSON.stringify({ 
                error: true, 
                message: 'Error fetching products from Boyner API.', 
                details: error.message 
            }),
            headers: { 'Content-Type': 'application/json' }
        };
    }
}; 