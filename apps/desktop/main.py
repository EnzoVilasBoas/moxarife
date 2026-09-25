from __future__ import annotations

import sys

from PySide6.QtWidgets import QApplication

from moxarife.api import ApiClient
from moxarife.api.client import Session
from moxarife.config import Settings
from moxarife.storage import TokenStore
from moxarife.ui import LoginWindow, MainWindow


class ApplicationController:
    def __init__(self, app: QApplication) -> None:
        self.app = app
        self.settings = Settings.from_environment()
        self.client = ApiClient(self.settings.api_url, self.settings.request_timeout)
        self.token_store = TokenStore()
        self.login_window: LoginWindow | None = None
        self.main_window: MainWindow | None = None

        session = self.token_store.load()
        if session is not None:
            self.client.restore(session)
            self.show_main(session)
            return

        self.show_login()

    def show_login(self) -> None:
        self.main_window = None
        self.login_window = LoginWindow(self.client, self.settings, self.token_store)
        self.login_window.logged_in.connect(self.show_main)
        self.login_window.show()

    def show_main(self, session: Session) -> None:
        if self.login_window is not None:
            self.login_window.close()
        self.login_window = None
        self.main_window = MainWindow(self.client, session, self.token_store)
        self.main_window.logged_out.connect(self.show_login)
        self.main_window.show()


def main() -> int:
    app = QApplication(sys.argv)
    app.setApplicationName("Moxarife")
    ApplicationController(app)
    return app.exec()


if __name__ == "__main__":
    raise SystemExit(main())
