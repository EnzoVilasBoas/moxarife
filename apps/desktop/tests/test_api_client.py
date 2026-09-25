from __future__ import annotations

import json
import sys
import unittest
from pathlib import Path
from unittest.mock import Mock, patch

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from moxarife.api.client import ApiClient, ApiError


class ApiClientTests(unittest.TestCase):
    def setUp(self) -> None:
        self.client = ApiClient("http://localhost:8080/api/v1")
        self.response = Mock()
        self.response.content = b'{"data": {"access_token": "a", "refresh_token": "r", "user": {"id": 1}}}'
        self.response.status_code = 200
        self.response.ok = True
        self.response.json.return_value = json.loads(self.response.content)

    @patch("moxarife.api.client.requests.Session.request")
    def test_login_sets_session(self, request: Mock) -> None:
        request.return_value = self.response

        session = self.client.login("user@example.com", "secret")

        self.assertEqual(session.access_token, "a")
        self.assertTrue(self.client.is_authenticated)

    @patch("moxarife.api.client.requests.Session.request")
    def test_api_error_uses_server_message(self, request: Mock) -> None:
        self.response.ok = False
        self.response.status_code = 422
        self.response.content = b'{"error": {"code": "validation_error", "message": "Campo invalido"}}'
        self.response.json.return_value = json.loads(self.response.content)
        request.return_value = self.response

        with self.assertRaises(ApiError) as context:
            self.client.login("user@example.com", "secret")

        self.assertEqual(context.exception.status_code, 422)
        self.assertEqual(str(context.exception), "Campo invalido")

    @patch("moxarife.api.client.requests.Session.request")
    def test_unauthorized_request_refreshes_once(self, request: Mock) -> None:
        login_response = Mock()
        login_response.content = b'{"data": {"access_token": "old", "refresh_token": "r", "user": {"id": 1}}}'
        login_response.status_code = 200
        login_response.ok = True
        login_response.json.return_value = json.loads(login_response.content)
        request.return_value = login_response
        self.client.login("user@example.com", "secret")

        unauthorized = Mock()
        unauthorized.content = b'{"error": {"code": "unauthorized", "message": "Expirado"}}'
        unauthorized.status_code = 401
        unauthorized.ok = False
        unauthorized.json.return_value = json.loads(unauthorized.content)
        refreshed = Mock()
        refreshed.content = b'{"data": {"access_token": "new", "refresh_token": "r2", "user": {"id": 1}}}'
        refreshed.status_code = 200
        refreshed.ok = True
        refreshed.json.return_value = json.loads(refreshed.content)
        result = Mock()
        result.content = b'{"data": {"items": [], "total": 0}}'
        result.status_code = 200
        result.ok = True
        result.json.return_value = json.loads(result.content)
        request.side_effect = [unauthorized, refreshed, result]

        payload = self.client.list_inventory()

        self.assertEqual(payload["total"], 0)
        self.assertEqual(self.client._session_data.access_token, "new")


if __name__ == "__main__":
    unittest.main()
