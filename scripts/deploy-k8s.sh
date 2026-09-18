#!/usr/bin/env bash
# ==============================================================================
# ShopNest — Kubernetes Zero-Downtime Deployment Script
# ==============================================================================
# Usage:
#   ./scripts/deploy-k8s.sh [<image_name_with_tag>] [<namespace>]
#
# Examples:
#   ./scripts/deploy-k8s.sh
#   ./scripts/deploy-k8s.sh vaibhavvv85/aws-ecommerce:42
#   ./scripts/deploy-k8s.sh vaibhavvv85/aws-ecommerce:latest shopnest
# ==============================================================================
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
MANIFEST_FILE="${SCRIPT_DIR}/k8s/shopnest.yaml"

# Default arguments
IMAGE="${1:-vaibhavvv85/aws-ecommerce:latest}"
NAMESPACE="${2:-shopnest}"

echo "====================================================="
echo "☸️  ShopNest Kubernetes Deployment: $(date)"
echo "📦 Target Container Image: ${IMAGE}"
echo "🏷️  Target Namespace:       ${NAMESPACE}"
echo "📄 Manifest File:          ${MANIFEST_FILE}"
echo "====================================================="

# 1. Verify Prerequisites
command -v kubectl >/dev/null 2>&1 || {
    echo "❌ Error: 'kubectl' CLI is not installed or not in PATH." >&2
    echo "Please install kubectl: https://kubernetes.io/docs/tasks/tools/" >&2
    exit 1
}

echo "🔍 Verifying Kubernetes cluster connectivity..."
if ! kubectl cluster-info >/dev/null 2>&1; then
    echo "❌ Error: Cannot connect to Kubernetes cluster." >&2
    echo "Please check your KUBECONFIG or cluster credentials." >&2
    exit 1
fi
echo "✅ Cluster connectivity verified."

# 2. Check manifest file
if [ ! -f "${MANIFEST_FILE}" ]; then
    echo "❌ Error: Manifest file not found at ${MANIFEST_FILE}" >&2
    exit 1
fi

# 3. Create or ensure namespace exists
echo "📁 Ensuring namespace '${NAMESPACE}' exists..."
kubectl create namespace "${NAMESPACE}" --dry-run=client -o yaml | kubectl apply -f -

# 4. Apply manifests
echo "📄 Applying base Kubernetes configurations and services..."
kubectl apply -f "${MANIFEST_FILE}" -n "${NAMESPACE}"

# 5. Perform rolling update with the specified image
echo "🔄 Updating deployment 'shopnest-app' to image: ${IMAGE}..."
kubectl set image deployment/shopnest-app app="${IMAGE}" -n "${NAMESPACE}"

# 6. Wait for rollout to complete (zero-downtime)
echo "⏳ Waiting for rolling update rollout to finish (timeout: 180s)..."
if kubectl rollout status deployment/shopnest-app -n "${NAMESPACE}" --timeout=180s; then
    echo "✅ Rolling update finished successfully!"
else
    echo "⚠️ Rollout did not complete within timeout. Current pod status:"
    kubectl get pods -n "${NAMESPACE}" -l app=shopnest
    exit 1
fi

# 7. Display active pods and endpoints
echo ""
echo "====================================================="
echo "📊 Current Pods in '${NAMESPACE}':"
echo "====================================================="
kubectl get pods -n "${NAMESPACE}" -l app=shopnest -o wide

echo ""
echo "====================================================="
echo "🌐 Kubernetes Services:"
echo "====================================================="
kubectl get svc -n "${NAMESPACE}"

echo ""
echo "====================================================="
echo "🎉 ShopNest Kubernetes deployment completed at $(date)!"
echo "====================================================="
