<?hh
// @format
enum UtilityBillType: string as string {
  Water = 'water';
  Elec = 'electricity';
}

final class Utility722 {
  const type Resident = shape(
    'name' => string,
    'start_date' => int,
    'end_date' => int, // The user may not move out yet.
    ?'paid' => float,
  );
  const type Bill =
    shape('begin_date' => int, 'end_date' => int, 'amount' => float);
  private dict<string, this::Resident> $residents =
    dict<string, this::Resident>[];
  private vec<this::Bill> $waterBills = vec<this::Bill>[];
  private vec<this::Bill> $elecBills = vec<this::Bill>[];

  private dict<int, float> $dailyWaterBillPerPerson = dict[];
  private dict<int, float> $dailyElecBillPerPerson = dict[];


  public function __construct() {
    $resident_file_name = "flib/logging/logger/resident.txt";
    $elec_bill_file_name = "flib/logging/logger/elec_bill.txt";
    $water_bill_file_name = "flib/logging/logger/water_bill.txt";

    foreach (Filesystem::yieldFile($resident_file_name) as $line) {
      $row_array = Str\split($line, " ");
      $this->residents[$row_array[0]] = shape(
        'name' => $row_array[0],
        'start_date' => Day::fromISOString($row_array[1])->getDaysSinceEpoch(),
        'end_date' => C\count($row_array) === 3
          ? Day::fromISOString($row_array[2])->getDaysSinceEpoch()
          : Day::fromDateTimeLocal(new DateTime('now'))->getDaysSinceEpoch(),
      );
    }

    foreach (Filesystem::yieldFile($elec_bill_file_name) as $line) {
      $row_array = Str\split($line, " ");
      invariant(
        Day::isValidISODate($row_array[0]),
        "%s is not valid",
        $row_array[0],
      );
      invariant(
        Day::isValidISODate($row_array[1]),
        "%s is not valid",
        $row_array[1],
      );
      $this->elecBills[] = shape(
        'begin_date' => Day::fromISOString($row_array[0])->getDaysSinceEpoch(),
        'end_date' => Day::fromISOString($row_array[1])->getDaysSinceEpoch(),
        'amount' => (float)$row_array[2],
      );
    }
    foreach (Filesystem::yieldFile($water_bill_file_name) as $line) {
      $row_array = Str\split($line, " ");
      $this->waterBills[] = shape(
        'begin_date' => Day::fromISOString($row_array[0])->getDaysSinceEpoch(),
        'end_date' => Day::fromISOString($row_array[1])->getDaysSinceEpoch(),
        'amount' => (float)$row_array[2],
      );
    }

    // validate bills. Make sure the next bill starts after the last bill.
    $this->validateBillDates($this->waterBills);
    $this->validateBillDates($this->elecBills);
    $this->calculateBillPerPersonPerDay(UtilityBillType::Elec);
    $this->calculateBillPerPersonPerDay(UtilityBillType::Water);
  }

  private function validateBillDates(vec<this::Bill> $bills): void {
    for ($i = 1; $i < C\count($bills); $i++) {
      if ($bills[$i]['begin_date'] - $bills[$i - 1]['end_date'] !== 1) {
        throw new Exception("wrong begin_date");
      }
    }
  }
  private function calculateBillPerPersonPerDay(
    UtilityBillType $bill_type,
  ): void {
    $bills = $bill_type == UtilityBillType::Elec
      ? $this->elecBills
      : $this->waterBills;
    foreach ($bills as $bill) {
      $price = $bill['amount'] / ($bill['end_date'] - $bill['begin_date'] + 1);
      for ($d = $bill['begin_date']; $d <= $bill['end_date']; $d++) {
        switch ($bill_type) {
          case UtilityBillType::Elec:
            $this->dailyElecBillPerPerson[$d] = $price /
              $this->getNumOfResidents($d);
            break;
          case UtilityBillType::Water:
            $this->dailyWaterBillPerPerson[$d] = $price /
              $this->getNumOfResidents($d);
            break;
        }
      }
    }
  }

  private function getNumOfResidents(int $d): int {
    $res = 0;
    foreach ($this->residents as $resident) {
      if (
        $d >= $resident['start_date'] &&
        ($resident['end_date'] === null || $d <= $resident['end_date'])
      ) {
        $res++;
      }
    }
    return $res;
  }

  public function getTotalBillForResident(
    string $name,
    UtilityBillType $bill_type,
  ): float {
    new DateTime('now');

    $resident = $this->residents[$name];
    return $this->getBillForResidentHelper(
      $resident['start_date'],
      $resident['end_date'],
      $bill_type,
    );
  }

  public function getBillForResident(
    string $name,
    string $begin,
    string $end,
    UtilityBillType $bill_type,
  ): float {
    invariant(
      Day::isValidISODate($begin) && Day::isValidISODate($end),
      "invliad date",
    );
    $resident = $this->residents[$name];
    Day::fromISOString($begin)->$start_date =
      Day::fromISOString($begin)->getDaysSinceEpoch();
    $end_date = Day::fromISOString($end)->getDaysSinceEpoch();
    invariant(
      $resident['start_date'] <= $start_date &&
        $end_date <= $resident['end_date'],
      'the start date or end date is not correct',
    );
    return $this->getBillForResidentHelper($start_date, $end_date, $bill_type);
  }

  private function getBillForResidentHelper(
    int $begin,
    int $end,
    UtilityBillType $bill_type,
  ): float {
    $res = 0.0;
    for ($d = $begin; $d <= $end; $d++) {
      switch ($bill_type) {
        case UtilityBillType::Elec:
          if (C\contains_key($this->dailyElecBillPerPerson, $d)) {
            $res += $this->dailyElecBillPerPerson[$d];
          }
          break;
        case UtilityBillType::Water:
          if (C\contains_key($this->dailyWaterBillPerPerson, $d)) {
            $res += $this->dailyWaterBillPerPerson[$d];
          }
          break;
      }
    }
    return $res;
  }
  public function getResidentNames(): vec<string> {
    $res = vec[];
    foreach ($this->residents as $name => $_) {
      $res[] = $name;
    }
    return $res;
  }
}
