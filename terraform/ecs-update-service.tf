# EventBridge rule to monitor ECR image pushes
resource "aws_cloudwatch_event_rule" "ecr_image_push" {
  name        = "ecr-image-push-rule"
  description = "Capture each ECR image push"

  event_pattern = jsonencode({
    source      = ["aws.ecr"]
    detail-type = ["ECR Image Action"]
    detail = {
      action-type = ["PUSH"]
      repository-name = [aws_ecr_repository.student_enrollment_api.name]
      image-tag      = ["latest"]
    }
  })
}

# IAM role for EventBridge to invoke Lambda
resource "aws_iam_role" "eventbridge_lambda" {
  name = "eventbridge-lambda-role"

  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Action = "sts:AssumeRole"
        Effect = "Allow"
        Principal = {
          Service = "events.amazonaws.com"
        }
      }
    ]
  })
}

# Lambda function to update ECS service
resource "aws_lambda_function" "update_ecs_service" {
  filename      = "${path.module}/lambda/update-ecs-service.zip"
  function_name = "update-ecs-service"
  role          = aws_iam_role.lambda_execution.arn
  handler       = "lambda_function.lambda_handler"  # Changed from index.handler
  runtime       = "python3.11"

  environment {
    variables = {
      ECS_CLUSTER = aws_ecs_cluster.laravel_cluster.name
      ECS_SERVICE = aws_ecs_service.main.name
    }
  }
}

# IAM role for Lambda execution
resource "aws_iam_role" "lambda_execution" {
  name = "lambda-execution-role"

  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Action = "sts:AssumeRole"
        Effect = "Allow"
        Principal = {
          Service = "lambda.amazonaws.com"
        }
      }
    ]
  })
}

# IAM policy for Lambda to update ECS service
resource "aws_iam_role_policy" "lambda_ecs_policy" {
  name = "lambda-ecs-policy"
  role = aws_iam_role.lambda_execution.id

  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Effect = "Allow"
        Action = [
          "ecs:UpdateService",
          "ecs:DescribeServices"
        ]
        Resource = [aws_ecs_service.main.id]
      }
    ]
  })
}

# CloudWatch Logs policy for Lambda
resource "aws_iam_role_policy_attachment" "lambda_logs" {
  role       = aws_iam_role.lambda_execution.name
  policy_arn = "arn:aws:iam::aws:policy/service-role/AWSLambdaBasicExecutionRole"
}

# EventBridge target to invoke Lambda
resource "aws_cloudwatch_event_target" "lambda" {
  rule      = aws_cloudwatch_event_rule.ecr_image_push.name
  target_id = "UpdateECSService"
  arn       = aws_lambda_function.update_ecs_service.arn
}

# Permission for EventBridge to invoke Lambda
resource "aws_lambda_permission" "eventbridge" {
  statement_id  = "AllowEventBridgeInvoke"
  action        = "lambda:InvokeFunction"
  function_name = aws_lambda_function.update_ecs_service.function_name
  principal     = "events.amazonaws.com"
  source_arn    = aws_cloudwatch_event_rule.ecr_image_push.arn
}