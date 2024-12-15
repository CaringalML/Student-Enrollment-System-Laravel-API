# Output the repository URL
output "repository_url" {
  description = "The URL of the ECR repository"
  value       = aws_ecr_repository.student_enrollment_api.repository_url
}

# Output the task definition ARN
output "task_definition_arn" {
  description = "ARN of the task definition"
  value       = aws_ecs_task_definition.laravel_app.arn
}

# Output the cluster ARN and Name
output "cluster_arn" {
  description = "ARN of the ECS cluster"
  value       = aws_ecs_cluster.laravel_cluster.arn
}

output "cluster_name" {
  description = "Name of the ECS cluster"
  value       = aws_ecs_cluster.laravel_cluster.name
}


output "certificate_arn" {
  value       = aws_acm_certificate.cert.arn
  description = "The ARN of the certificate"
}

output "zone_id" {
  value       = data.aws_route53_zone.domain.zone_id
  description = "The Route 53 Zone ID"
}




# Outputs
output "rds_endpoint" {
  description = "RDS instance endpoint"
  value       = aws_db_instance.caringal.endpoint
}

output "rds_port" {
  description = "RDS instance port"
  value       = aws_db_instance.caringal.port
}