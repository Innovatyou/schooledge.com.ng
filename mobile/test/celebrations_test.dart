import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:schooledge_mobile/features/celebrations/presentation/celebration_popup.dart';

void main() {
  final items = [
    {
      'key': 'exam-60-445',
      'theme': 'fireworks',
      'emoji': '🏆',
      'title': 'You came 1st in JSS 1 A!',
      'message': 'Keep shining!',
      'photo': null,
    },
    {
      'key': 'pick-3',
      'theme': 'balloons',
      'emoji': '🎉',
      'title': "Founders' Day",
      'message': '',
      'photo': null,
    },
  ];

  Future<BuildContext> pumpHost(WidgetTester tester) async {
    late BuildContext ctx;
    await tester.pumpWidget(
      MaterialApp(
        home: Builder(
          builder: (context) {
            ctx = context;
            return const Scaffold();
          },
        ),
      ),
    );
    return ctx;
  }

  testWidgets('shows each celebration in turn with its animation', (
    tester,
  ) async {
    SharedPreferences.setMockInitialValues({});
    final context = await pumpHost(tester);
    showCelebrations(context, items);
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 500));

    expect(find.text('You came 1st in JSS 1 A!'), findsOneWidget);
    expect(find.text('Next (2 of 2)'), findsOneWidget);

    await tester.tap(find.text('Next (2 of 2)'));
    await tester.pump(const Duration(milliseconds: 500));
    expect(find.text("Founders' Day"), findsOneWidget);

    await tester.tap(find.text('Thank you!'));
    await tester.pumpAndSettle(const Duration(seconds: 1));
    expect(find.text("Founders' Day"), findsNothing);
  });

  testWidgets('with once, a celebration only pops up the first time', (
    tester,
  ) async {
    SharedPreferences.setMockInitialValues({});
    final context = await pumpHost(tester);
    await showCelebrationsAndClose(tester, context, items);

    showCelebrations(context, items, once: true);
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 500));
    expect(find.text('You came 1st in JSS 1 A!'), findsOneWidget);
    await tester.tapAt(const Offset(5, 5)); // outside the card
    await tester.pumpAndSettle(const Duration(seconds: 1));

    showCelebrations(context, items, once: true);
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 500));
    expect(find.text('You came 1st in JSS 1 A!'), findsNothing);
  });
}

// a plain (not once) showing doesn't count as seen
Future<void> showCelebrationsAndClose(
  WidgetTester tester,
  BuildContext context,
  List<Map<String, dynamic>> items,
) async {
  showCelebrations(context, items);
  await tester.pump();
  await tester.pump(const Duration(milliseconds: 500));
  await tester.tapAt(const Offset(5, 5));
  await tester.pumpAndSettle(const Duration(seconds: 1));
}
