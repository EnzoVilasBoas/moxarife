from __future__ import annotations

from dataclasses import dataclass
from typing import Any

import requests


class ApiError(RuntimeError):
    def __init__(self, message: str, status_code: int | None = None, code: str | None = None):
        super().__init__(message)
        self.status_code = status_code
        self.code = code


@dataclass(frozen=True)
class Session:
    access_token: str
    refresh_token: str
    user: dict[str, Any]


class ApiClient:
    def __init__(self, base_url: str, timeout: float = 15.0) -> None:
        self.base_url = base_url.rstrip("/")
        self.timeout = timeout
        self.session = requests.Session()
        self.session.headers.update({"Accept": "application/json", "Content-Type": "application/json"})
        self._session_data: Session | None = None

    @property
    def is_authenticated(self) -> bool:
        return self._session_data is not None

    def restore(self, session: Session) -> None:
        self._session_data = session

    def login(self, email: str, password: str) -> Session:
        payload = self._request("POST", "/auth/login", json_body={"email": email, "password": password})
        self._session_data = Session(
            access_token=payload["access_token"],
            refresh_token=payload["refresh_token"],
            user=payload["user"],
        )
        return self._session_data

    def logout(self) -> None:
        if self._session_data is not None:
            try:
                self._request(
                    "POST",
                    "/auth/logout",
                    json_body={"refresh_token": self._session_data.refresh_token},
                    authenticated=False,
                )
            finally:
                self._session_data = None
        else:
            self._session_data = None

    def refresh(self) -> Session:
        if self._session_data is None:
            raise ApiError("Não há sessão para renovar.")

        payload = self._request(
            "POST",
            "/auth/refresh",
            json_body={"refresh_token": self._session_data.refresh_token},
            authenticated=False,
        )
        self._session_data = Session(
            access_token=payload["access_token"],
            refresh_token=payload["refresh_token"],
            user=payload["user"],
        )
        return self._session_data

    def list_inventory(self, page: int = 1, per_page: int = 20, search: str = "") -> dict[str, Any]:
        return self._request(
            "GET",
            "/inventory/items",
            params={"page": page, "per_page": per_page, "search": search},
        )

    def create_inventory_item(self, data: dict[str, Any]) -> dict[str, Any]:
        return self._request("POST", "/inventory/items", json_body=data)

    def health(self) -> dict[str, Any]:
        return self._request("GET", "/health", base_url=self.base_url.removesuffix("/api/v1"), authenticated=False)

    def _request(
        self,
        method: str,
        path: str,
        *,
        json_body: dict[str, Any] | None = None,
        params: dict[str, Any] | None = None,
        authenticated: bool = True,
        base_url: str | None = None,
        allow_refresh: bool = True,
    ) -> dict[str, Any]:
        headers = {}
        if authenticated and self._session_data is not None:
            headers["Authorization"] = f"Bearer {self._session_data.access_token}"

        url = f"{(base_url or self.base_url).rstrip('/')}/{path.lstrip('/')}"
        try:
            response = self.session.request(
                method,
                url,
                headers=headers,
                json=json_body,
                params=params,
                timeout=self.timeout,
            )
        except requests.RequestException as exc:
            raise ApiError("Não foi possível conectar à API.") from exc

        try:
            payload = response.json() if response.content else {}
        except ValueError as exc:
            raise ApiError("A API retornou uma resposta inválida.", response.status_code) from exc

        if response.status_code == 401 and authenticated and allow_refresh and self._session_data is not None:
            try:
                self.refresh()
            except ApiError:
                self._session_data = None
                raise
            return self._request(
                method,
                path,
                json_body=json_body,
                params=params,
                authenticated=authenticated,
                base_url=base_url,
                allow_refresh=False,
            )

        if not response.ok:
            error = payload.get("error", {}) if isinstance(payload, dict) else {}
            message = error.get("message") or f"Falha na API ({response.status_code})."
            raise ApiError(message, response.status_code, error.get("code"))

        if isinstance(payload, dict) and "data" in payload:
            return payload["data"]
        return payload if isinstance(payload, dict) else {"data": payload}
