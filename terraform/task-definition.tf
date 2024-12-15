# Task Definition for Laravel Application
resource "aws_ecs_task_definition" "laravel_app" {
  family                   = "student-enrollment-laravel-api"
  requires_compatibilities = ["EC2", "FARGATE"]
  network_mode            = "awsvpc"
  cpu                     = var.container_cpu
  memory                  = var.container_memory
  execution_role_arn      = aws_iam_role.ecs_execution_role.arn
  task_role_arn           = aws_iam_role.ecs_task_role.arn

  runtime_platform {
    operating_system_family = "LINUX"
    cpu_architecture       = "X86_64"
  }

  container_definitions = jsonencode([
    {
      name         = "student-enrollment-laravel-api"
      image        = "${aws_ecr_repository.student_enrollment_api.repository_url}:latest"
      essential    = true

      portMappings = [
        {
          containerPort = var.container_port
          hostPort      = var.container_port
          protocol      = "tcp"
        }
      ]

      # Sensitive values fetched from Secrets Manager
      secrets = [
        {
          name      = "APP_KEY"
          valueFrom = "${aws_secretsmanager_secret.laravel_secrets.arn}:APP_KEY::"
        },
        {
          name      = "DB_PASSWORD"
          valueFrom = "${aws_secretsmanager_secret.laravel_secrets.arn}:DB_PASSWORD::"
        },
        {
          name      = "AWS_ACCESS_KEY_ID"
          valueFrom = "${aws_secretsmanager_secret.laravel_secrets.arn}:AWS_ACCESS_KEY_ID::"
        },
        {
          name      = "AWS_SECRET_ACCESS_KEY"
          valueFrom = "${aws_secretsmanager_secret.laravel_secrets.arn}:AWS_SECRET_ACCESS_KEY::"
        },
        {
          name      = "MAIL_PASSWORD"
          valueFrom = "${aws_secretsmanager_secret.laravel_secrets.arn}:MAIL_PASSWORD::"
        },
        {
          name      = "REDIS_PASSWORD"
          valueFrom = "${aws_secretsmanager_secret.laravel_secrets.arn}:REDIS_PASSWORD::"
        },
        {
          name      = "PUSHER_APP_KEY"
          valueFrom = "${aws_secretsmanager_secret.laravel_secrets.arn}:PUSHER_APP_KEY::"
        },
        {
          name      = "PUSHER_APP_SECRET"
          valueFrom = "${aws_secretsmanager_secret.laravel_secrets.arn}:PUSHER_APP_SECRET::"
        }
      ]

      # Non-sensitive environment variables
      environment = [
        # Application Configuration
        {
          name  = "APP_NAME"
          value = var.app_name
        },
        {
          name  = "APP_ENV"
          value = var.environment
        },
        {
          name  = "APP_DEBUG"
          value = var.app_debug
        },
        {
          name  = "APP_URL"
          value = var.app_url
        },

        # Logging Configuration
        {
          name  = "LOG_CHANNEL"
          value = var.log_channel
        },
        {
          name  = "LOG_DEPRECATIONS_CHANNEL"
          value = var.log_deprecations_channel
        },
        {
          name  = "LOG_LEVEL"
          value = var.log_level
        },

        # Database Configuration
        {
          name  = "DB_CONNECTION"
          value = var.db_connection
        },
        {
          name  = "DB_HOST"
          value = split(":", aws_db_instance.caringal.endpoint)[0]
        },
        {
          name  = "DB_PORT"
          value = tostring(var.db_port)
        },
        {
          name  = "DB_DATABASE"
          value = var.db_name
        },
        {
          name  = "DB_USERNAME"
          value = var.db_username
        },

        # Cache and Session Configuration
        {
          name  = "BROADCAST_DRIVER"
          value = var.broadcast_driver
        },
        {
          name  = "CACHE_DRIVER"
          value = var.cache_driver
        },
        {
          name  = "FILESYSTEM_DISK"
          value = var.filesystem_disk
        },
        {
          name  = "QUEUE_CONNECTION"
          value = var.queue_connection
        },
        {
          name  = "SESSION_DRIVER"
          value = var.session_driver
        },
        {
          name  = "SESSION_LIFETIME"
          value = var.session_lifetime
        },

        # Redis Configuration
        {
          name  = "REDIS_HOST"
          value = var.redis_host
        },
        {
          name  = "REDIS_PORT"
          value = var.redis_port
        },

        # Memcached Configuration
        {
          name  = "MEMCACHED_HOST"
          value = var.memcached_host
        },

        # Mail Configuration
        {
          name  = "MAIL_MAILER"
          value = var.mail_mailer
        },
        {
          name  = "MAIL_HOST"
          value = var.mail_host
        },
        {
          name  = "MAIL_PORT"
          value = var.mail_port
        },
        {
          name  = "MAIL_USERNAME"
          value = var.mail_username
        },
        {
          name  = "MAIL_ENCRYPTION"
          value = var.mail_encryption
        },
        {
          name  = "MAIL_FROM_ADDRESS"
          value = var.mail_from_address
        },
        {
          name  = "MAIL_FROM_NAME"
          value = var.mail_from_name
        },

        # AWS Configuration
        {
          name  = "AWS_DEFAULT_REGION"
          value = var.aws_region
        },
        {
          name  = "AWS_BUCKET"
          value = var.aws_bucket
        },
        {
          name  = "AWS_USE_PATH_STYLE_ENDPOINT"
          value = var.aws_use_path_style_endpoint
        },

        # CloudFront Configuration
        {
          name  = "CLOUDFRONT_DOMAIN"
          value = var.cloudfront_domain
        },

        # Pusher Configuration
        {
          name  = "PUSHER_APP_ID"
          value = var.pusher_app_id
        },
        {
          name  = "PUSHER_HOST"
          value = var.pusher_host
        },
        {
          name  = "PUSHER_PORT"
          value = var.pusher_port
        },
        {
          name  = "PUSHER_SCHEME"
          value = var.pusher_scheme
        },
        {
          name  = "PUSHER_APP_CLUSTER"
          value = var.pusher_app_cluster
        },

        # Vite Configuration
        {
          name  = "VITE_PUSHER_APP_KEY"
          value = var.vite_pusher_app_key
        },
        {
          name  = "VITE_PUSHER_HOST"
          value = var.vite_pusher_host
        },
        {
          name  = "VITE_PUSHER_PORT"
          value = var.vite_pusher_port
        },
        {
          name  = "VITE_PUSHER_SCHEME"
          value = var.vite_pusher_scheme
        },
        {
          name  = "VITE_PUSHER_APP_CLUSTER"
          value = var.vite_pusher_app_cluster
        }
      ]

      logConfiguration = {
        logDriver = "awslogs"
        options = {
          "awslogs-group"         = aws_cloudwatch_log_group.laravel_app.name
          "awslogs-region"        = var.aws_region
          "awslogs-stream-prefix" = "ecs"
        }
      }

      mountPoints = []
      volumesFrom = []
    }
  ])

  tags = var.default_tags
}