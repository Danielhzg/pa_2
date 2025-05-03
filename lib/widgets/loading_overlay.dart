import 'package:flutter/material.dart';

class LoadingOverlay {
  final BuildContext _context;
  OverlayEntry? _overlay;

  LoadingOverlay._(context) : _context = context;

  factory LoadingOverlay.of(BuildContext context) {
    return LoadingOverlay._(context);
  }

  void show() {
    if (_overlay == null) {
      _overlay = OverlayEntry(
        builder: (context) => ColoredBox(
          color: Colors.black.withOpacity(0.5),
          child: Center(
            child: Container(
              padding: const EdgeInsets.all(16.0),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(10.0),
              ),
              child: const CircularProgressIndicator(
                color: Color(0xFFFF87B2),
              ),
            ),
          ),
        ),
      );
      Overlay.of(_context).insert(_overlay!);
    }
  }

  void hide() {
    if (_overlay != null) {
      _overlay!.remove();
      _overlay = null;
    }
  }
}
