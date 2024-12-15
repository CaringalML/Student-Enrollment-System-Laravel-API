# Create a secrets manager secret for Laravel application secrets
resource "aws_secretsmanager_secret" "laravel_secrets" {
  name                    = "student-record-system-laravel-secrets"
  description            = "Secrets for Laravel application"
  recovery_window_in_days = 0
  tags                    = var.default_tags
}

# Store the sensitive values in JSON format
resource "aws_secretsmanager_secret_version" "laravel_secrets_version" {
  secret_id = aws_secretsmanager_secret.laravel_secrets.id
  secret_string = jsonencode({
    APP_KEY               = var.app_key
    DB_PASSWORD          = var.db_password
    AWS_ACCESS_KEY_ID    = var.aws_access_key_id
    AWS_SECRET_ACCESS_KEY = var.aws_secret_access_key
    MAIL_PASSWORD        = var.mail_password
    REDIS_PASSWORD       = var.redis_password
    PUSHER_APP_KEY      = var.pusher_app_key
    PUSHER_APP_SECRET   = var.pusher_app_secret
  })
}

# Update the ECS execution role to allow access to Secrets Manager
resource "aws_iam_role_policy" "ecs_execution_role_secrets_policy" {
  name = "ecs-execution-role-secrets-policy"
  role = aws_iam_role.ecs_execution_role.id

  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Effect = "Allow"
        Action = [
          "secretsmanager:GetSecretValue",
          "kms:Decrypt"
        ]
        Resource = [
          aws_secretsmanager_secret.laravel_secrets.arn
        ]
      }
    ]
  })
}