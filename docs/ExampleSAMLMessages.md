## Example SAML Messages

The example messages of a SAML 2.0 "SP initiated" "WEB-SSO" authentication. The messages are ordered following the SAML flow. The flow assumes that on of  the second factors authentication methods that are build into the Stepup-Gateway is used (i.e. SMS or Yubikey). It does not include the SAML exchange of GSSP second factor authentication.

This is a normal authentication (i.e. not SFO).

### From Service Provider to Stepup-Gateway

```xml
<samlp:AuthnRequest
    xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"
    xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion"
    ID="_123456789012345678901234567890123456789012"
    Version="2.0"
    IssueInstant="2014-10-22T11:06:59Z"
    Destination="https://gateway.org/sso"
    AssertionConsumerServiceURL="https://sp.com/acs"
    ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST">
  <saml:Issuer>https://sp.com/metadata</saml:Issuer>
  <samlp:RequestedAuthnContext>
    <saml:AuthnContextClassRef>http://suaas.example.com/assurance/loa2</saml:AuthnContextClassRef>
  </samlp:RequestedAuthnContext>
</samlp:AuthnRequest>
```


### AuthnRequest from Stepup=Gateway to IDP

```xml
<samlp:AuthnRequest
    xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"
    xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion"
    ID="_000000aabbccddeeffaabbccddeeffaabbccddeeff"
    Version="2.0" IssueInstant="2014-10-22T11:06:59Z"
    Destination="https://idp.edu/single-sign-on"
    AssertionConsumerServiceURL="https://gateway.org/acs"
    ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST">
  <saml:Issuer>https://gateway.org/metadata</saml:Issuer>
  <samlp:Scoping ProxyCount="10">
    <samlp:RequesterID>https://sp.com/metadata</samlp:RequesterID>
  </samlp:Scoping>
</samlp:AuthnRequest>
```


### Respronse from Remote IdP to Stepup-Gateway

```xml
<samlp:Response
    xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"
    xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion"
    ID="CORTO1111111111222222222233333333334444444444"
    Version="2.0"
    IssueInstant="2014-10-22T11:07:08Z"
    Destination="https://gateway.org/acs"
    InResponseTo="_000000aabbccddeeffaabbccddeeffaabbccddeeff">
  <saml:Issuer>https://idp.edu/metadata</saml:Issuer>
  <samlp:Status>
    <samlp:StatusCode Value="urn:oasis:names:tc:SAML:2.0:status:Success"/>
  </samlp:Status>
  <saml:Assertion
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xmlns:xs="http://www.w3.org/001/XMLSchema"
        ID="CORTOaabbccddeeaabbccddeeaabbccddeeaabbccddee"
        Version="2.0"
        IssueInstant="2014-10-22T11:07:08Z">
    <saml:Issuer>https://idp.edu/metadata</saml:Issuer>
    <ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
      <ds:SignedInfo>
        <ds:CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>
        <ds:SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"/>
        <ds:Reference URI="#CORTOaabbccddeeaabbccddeeaabbccddeeaabbccddee">
          <ds:Transforms>
            <ds:Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"/>
            <ds:Transform Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>
          </ds:Transforms>
          <ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>
          <ds:DigestValue>8sSP1bB5cQkwraJZ7RejoVaEjtM=</ds:DigestValue>
        </ds:Reference>
      </ds:SignedInfo>
      <ds:SignatureValue>QZr...==</ds:SignatureValue>
      <ds:KeyInfo>
        <ds:X509Data>
          <ds:X509Certificate>MII...=</ds:X509Certificate>
        </ds:X509Data>
      </ds:KeyInfo>
    </ds:Signature>
    <saml:Subject>
      <saml:NameID Format="urn:oasis:names:tc:SAML:2.0:nameid-format:persistent">724cca6778a1d3db16b65c40d4c378d011f220be</saml:NameID>
      <saml:SubjectConfirmation Method="urn:oasis:names:tc:SAML:2.0:cm:bearer">
        <saml:SubjectConfirmationData
            NotOnOrAfter="2014-1022T11:12:08Z"
            Recipient="https://gateway.org/acs"
            InResponseTo="_000000aabbccddeeffaabbccddeeffaabbccddeeff"/>
      </saml:SubjectConfirmation>
    </saml:Subject>
    <saml:Conditions NotBefore="2014-10-22T11:07:07Z" NotOnOrAfter="2014-10-22T11:12:08Z">
      <saml:AudienceRestriction>
        <saml:Audience>https://gateway.org/metadata</saml:Audience>
      </saml:AudienceRestriction>
    </saml:Conditions>
    <saml:AuthnStatement
            AuthnInstant="2014-10-22T11:07:07"
            SessionNotOnOrAfter="2014-10-22T19:07:07Z"
            SessionIndex="_1dad5d4bf289a5761a62fedf91143816d323a0604b">
      <saml:AuthnContext>
        <saml:AuthnContextClassRef>urn:oasis:names:tc:SAML:2.0:ac:classes:Password</saml:AuthnContextClassRef>
        <saml:AuthenticatingAuthority>https://proxied-idp.edu/</saml:AuthenticatingAuthority>
      </saml:AuthnContext>
    </saml:AuthnStatement>
    <saml:AttributeStatement>
      <saml:Attribute Name="urn:oid:0.9.2342.19200300.100.1.3" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
        <saml:AttributeValue xsi:type="xs:string">john.doe@example.edu</saml:AttributeValue>
      </saml:Attribute>
      <saml:Attribute Name="urn:oid:1.3.6.1.4.1.5923.1.1.1.10" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
        <saml:AttributeValue>
          <saml:NameID Format="urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified">urn:collab:person:example.edu:jdoe</saml:NameID>
        </saml:AttributeValue>
      </saml:Attribute>
    </saml:AttributeStatement>
  </saml:Assertion>
</samlp:Response>
```

### Response from Stepup-Gateway to Service Provider

```xml
<samlp:Response
    xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"
    xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion"
    ID="aaaaaaaaaabbbbbbbbbbccccccccccdddddddddd"
    Version="2.0"
    IssueInstant="2014-10-22T11:07:08Z"
    Destination="https://sp.com/acs"
    InResponseTo="_123456789012345678901234567890123456789012">
  <saml:Issuer>https://gateway.org/metadata</saml:Issuer>
  <samlp:Status>
    <samlp:StatusCode Value="urn:oasis:names:tc:SAML:2.0:status:Success"/>
  </samlp:Status>
  <saml:Assertion
         xmlns:xsi="http://www.w3.org/2001/XMLSchema-istance"
         xmlns:xs="http://www.w3.org/2001/XMLSchema"
         ID="pfx12345678-aaaa-bbbb-cccc-112233445566"
         Version="2.0"
         IssueInstant="2014-10-22T11:07:08Z">
    <saml:Issuer>https://gateway.org/metadata</saml:Issuer>
    <ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
      <ds:SignedInfo>
        <ds:CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>
        <ds:SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"/>
        <ds:Reference URI="#pfx12345678-aaaa-bbbb-cccc-112233445566">
          <ds:Transforms>
            <ds:Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"/>
            <ds:Transform Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>
          </ds:Transforms>
          <ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>
          <ds:DigestValue>0WTs8OzUZo9jve/N0PzWsiTF40s=</ds:DigestValue>
        </ds:Reference>
      </ds:SignedInfo>
      <ds:SignatureValue>ce2...</ds:SignatureValue>
      <ds:KeyInfo>
        <ds:X509Data>
          <ds:X509Certificate>MII...</ds:X509Certificate>
        </ds:X509Data>
      </ds:KeyInfo>
    </ds:Signature>
    <saml:Subject>
      <saml:NameID Format="urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified">urn:collab:person:example.edu:jdoe</saml:NameID>
      <saml:SubjectConfirmation Method="urn:oasis:names:tc:SAML:2.0:cm:bearer">
        <saml:SubjectConfirmationData
            NotOnOrAfter="2014-1022T11:12:08Z"
            Recipient="https://sp.com/acs"
            InResponseTo="_123456789012345678901234567890123456789012"/>
      </saml:SubjectConfirmation>
    </saml:Subject>
    <saml:Conditions NotBefore="2014-10-22T11:07:07Z" NotOnOrAfter="2014-10-22T11:12:08Z">
      <saml:AudienceRestriction>
        <saml:Audience>https://sp.com/metadata</saml:Audience>
      </saml:AudienceRestriction>
    </saml:Conditions>
    <saml:AuthnStatement
        AuthnInstant="2014-10-22T11:07:07">
      <saml:AuthnContext>
        <saml:AuthnContextClassRef>http://suaas.example.com/assurance/loa3</saml:AuthnContextClassRef>
        <saml:AuthenticatingAuthority>https://proxied-idp.edu/</saml:AuthenticatingAuthority>
        <saml:AuthenticatingAuthority>https://idp.edu/metadata</saml:AuthenticatingAuthority>
      </saml:AuthnContext>
    </saml:AuthnStatement>
    <saml:AttributeStatement>
      <saml:Attribute Name="urn:oid:0.9.2342.19200300.100.1.3" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
        <saml:AttributeValue xsi:type="xs:string">john.doe@example.edu</saml:AttributeValue>
      </saml:Attribute>
      <saml:Attribute Name="urn:oid:1.3.6.1.4.1.5923.1.1.1.10" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
        <saml:AttributeValue>
          <saml:NameID Format="urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified">urn:collab:person:example.edu:jdoe</saml:NameID>
        </saml:AttributeValue>
      </saml:Attribute>
    </saml:AttributeStatement>
  </saml:Assertion>
</samlp:Response>
```

## Second Factor Only (SFO) authentication

