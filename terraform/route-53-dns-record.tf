# Create A record for server.martincaringal.co.nz
resource "aws_route53_record" "server" {
  zone_id = data.aws_route53_zone.domain.zone_id
  name    = "server.martincaringal.co.nz"
  type    = "A"

  alias {
    name                   = aws_lb.main.dns_name
    zone_id               = aws_lb.main.zone_id
    evaluate_target_health = true
  }
}

# Output the DNS name
output "server_dns" {
  value       = aws_route53_record.server.name
  description = "The DNS name for the server subdomain"
}