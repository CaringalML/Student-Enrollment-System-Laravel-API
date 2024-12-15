# Get the Route 53 zone
data "aws_route53_zone" "domain" {
  name = "martincaringal.co.nz"
  private_zone = false
}

# ACM Certificate
resource "aws_acm_certificate" "cert" {
  domain_name       = "martincaringal.co.nz"
  validation_method = "DNS"

  # Include wildcard and apex domain
  subject_alternative_names = ["*.martincaringal.co.nz"]

  tags = {
    Environment = "production"
    Name        = "martincaringal-wildcard-certificate"
  }

  lifecycle {
    create_before_destroy = true
  }
}

# Create Route 53 records for certificate validation
resource "aws_route53_record" "cert_validation" {
  for_each = {
    for dvo in aws_acm_certificate.cert.domain_validation_options : dvo.domain_name => {
      name   = dvo.resource_record_name
      record = dvo.resource_record_value
      type   = dvo.resource_record_type
    }
  }

  allow_overwrite = true
  name            = each.value.name
  records         = [each.value.record]
  ttl             = 60
  type            = each.value.type
  zone_id         = data.aws_route53_zone.domain.zone_id
}

# Certificate Validation
resource "aws_acm_certificate_validation" "cert_validation" {
  certificate_arn         = aws_acm_certificate.cert.arn
  validation_record_fqdns = [for record in aws_route53_record.cert_validation : record.fqdn]
}

