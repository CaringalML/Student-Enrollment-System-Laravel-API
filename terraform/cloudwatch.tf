# CloudWatch Log Group
resource "aws_cloudwatch_log_group" "laravel_app" {
  name              = "/ecs/student-enrollment-laravel-api"
  retention_in_days = 30

  tags = var.default_tags
}