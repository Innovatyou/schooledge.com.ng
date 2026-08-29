import 'dart:convert';

import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../domain/todo_item.dart';

const _prefsKey = 'schooledge.todos';

final todoListProvider = NotifierProvider<TodoListController, List<TodoItem>>(
  TodoListController.new,
);

/// A student's personal task list - deliberately local-only (shared_preferences,
/// one JSON-encoded list), no backend endpoint. Unlike the school-managed
/// Planner schedule (read-only, server-driven), this is the student's own
/// private, freely editable list.
class TodoListController extends Notifier<List<TodoItem>> {
  @override
  List<TodoItem> build() {
    _restore();
    return const [];
  }

  Future<void> _restore() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getStringList(_prefsKey);
    if (raw == null) return;
    state = raw
        .map(
          (entry) =>
              TodoItem.fromJson(jsonDecode(entry) as Map<String, dynamic>),
        )
        .toList();
  }

  Future<void> _persist() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setStringList(
      _prefsKey,
      state.map((item) => jsonEncode(item.toJson())).toList(),
    );
  }

  Future<void> add(String title, {String? notes, DateTime? dueDate}) async {
    final item = TodoItem(
      id: DateTime.now().microsecondsSinceEpoch.toString(),
      title: title,
      notes: notes,
      dueDate: dueDate,
    );
    state = [...state, item];
    await _persist();
  }

  Future<void> toggleDone(String id) async {
    state = [
      for (final item in state)
        if (item.id == id) item.copyWith(done: !item.done) else item,
    ];
    await _persist();
  }

  Future<void> update(
    String id, {
    String? title,
    String? notes,
    DateTime? dueDate,
    bool clearDueDate = false,
  }) async {
    state = [
      for (final item in state)
        if (item.id == id)
          item.copyWith(
            title: title,
            notes: notes,
            dueDate: dueDate,
            clearDueDate: clearDueDate,
          )
        else
          item,
    ];
    await _persist();
  }

  Future<void> remove(String id) async {
    state = state.where((item) => item.id != id).toList();
    await _persist();
  }
}
