# Gateway APIs

## SMS

### Request
URL: `https://gateway.tld/api/send-sms`  
Method: POST  
```json
{
    "requester": {
        "institution": "SURFnet",
        "identity": "d12cb994-5719-405a-9533-af1beef78ee3"
    },
    "message": {
        "originator": "SURFnet",
        "recipient": "31610101010",
        "body": "DCAF83"
    }
}
```
### Responses  
On *Success* `200 OK`  
```json
{
    "status": "OK"
}
```
MessageBird delivery status is not available for lib/bundle consumers

On *Invalid Request* `400 BAD REQUEST`
```json
{
    "errors": [
        "Invalid phonenumber format"
    ]
}
```

On *Failure* `502 Bad Gateway`  
```json
{
    "errors": [
        "Not enough balance (25)"
    ]
}
```
Messagebird error


## Yubikey

### Request
URL: `https://gateway.tld/api/verify-yubikey`  
Method: POST  
```json
{
    "requester": {
        "institution":"institution",
        "identity":"UUID"
    },
    "otp": { 
        "value": "ccccccbtbhnhgvjjickhkndrllvfcerkrcdttbtigduc"
    }
}
```
### Responses  
On *Success* `200 OK`  
```json
{
    "status": "OK"
}
```

On *Invalid Request* `400 BAD REQUEST`
```json
```

On *Failure* `502 Bad Gateway`  
```json
```
